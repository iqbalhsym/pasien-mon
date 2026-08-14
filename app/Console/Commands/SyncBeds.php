<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Floor;
use App\Models\Wing;
use App\Models\Room;
use App\Models\Bed;
use App\Models\Equipment;
use App\Models\Maintenance;
use Carbon\Carbon;

class SyncBeds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:beds {--force : Force sync and bypass cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch beds occupancy data from RSUI external API and synchronize it to the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting bed synchronization...');
        DB::disableQueryLog();

        $apiUrl = 'https://10.121.1.115/api/external/beds-occupancy';
        $apiKey = 'rsui_bed_mon_secret_key_2026';

        try {
            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
                'Content-Type' => 'application/json',
                'Host' => 'bed-monitoring.rs.ui.ac.id'
            ])->withoutVerifying()->timeout(10)->get($apiUrl);

            if (!$response->successful()) {
                $this->error('Failed to fetch data from API. HTTP Status: ' . $response->status());
                return 1;
            }

            $body = $response->json();
            if (!isset($body['success']) || !$body['success'] || !isset($body['data'])) {
                $this->error('API returned unsuccessful response status or missing data.');
                return 1;
            }

            $floorsData = $body['data'];

            $force = $this->option('force');

            // How often we re-check Afya for a patient who is already known and still occupying the
            // same bed. Without this, a patient can get a brand new registration in Afya mid-stay
            // (e.g. a procedure, class change, re-verification) without ever leaving their bed slot
            // in the bed-monitoring source, and their registered_date/dpjp_utama would then stay
            // frozen on the old value forever (only a new admission or --force would ever refresh it).
            $refreshIntervalHours = (int) env('AFYA_REFRESH_INTERVAL_HOURS', 6);

            // Cap how many "already known, just due for periodic refresh" patients this single
            // (non-force) run will fetch. On a cold start (or right after deploying this feature)
            // NONE of the currently occupied patients have the periodic marker yet, so without this
            // cap every regular 5-minute cron run would try to refetch the entire occupied ward at
            // once — hundreds of sequential Afya calls — turning the routine cron into a de facto
            // --force run and starving it of time to promptly pick up genuinely new admissions.
            // New admissions and never-yet-populated patients are NOT subject to this cap; only this
            // periodic "catch up" refresh is throttled, and it naturally spreads across many cron
            // cycles until every patient has a fresh marker.
            $periodicRefreshBatchLimit = (int) env('AFYA_PERIODIC_REFRESH_BATCH', 15);
            $periodicRefreshCount = 0;

            // Get already cached/populated patient details from the local database
            $existingMrnMap = [];
            $currentlyOccupiedRms = [];
            if (!$force) {
                $existingMrnMap = Equipment::whereNotNull('registered_date')
                    ->where('registered_date', '!=', '')
                    ->whereNotNull('dpjp_utama')
                    ->where('dpjp_utama', '!=', '')
                    ->pluck('serial_number')
                    ->toArray();
                $existingMrnMap = array_flip($existingMrnMap);

                // Get patients currently occupying beds in local database
                $currentlyOccupiedRms = Equipment::whereHas('bed')
                    ->pluck('serial_number')
                    ->toArray();
                $currentlyOccupiedRms = array_flip($currentlyOccupiedRms);
            }

            // 1. Collect all unique patient MRNs to pre-fetch outside database transaction
            $patientRmList = [];
            foreach ($floorsData as $floorData) {
                foreach ($floorData['wings'] ?? [] as $wingData) {
                    foreach ($wingData['rooms'] ?? [] as $roomData) {
                        foreach ($roomData['beds'] ?? [] as $bedData) {
                            $patientData = $bedData['patient'] ?? null;
                            if (!empty($patientData) && !empty($patientData['no_rm'])) {
                                $noRm = trim($patientData['no_rm']);
                                if (strtoupper($noRm) !== 'TERDAFTAR' && strpos($noRm, 'BOOKING-') !== 0) {
                                    $isNewAdmission = !$force && !isset($currentlyOccupiedRms[$noRm]);
                                    // Periodic refresh: even if already known and still in the same bed,
                                    // re-check Afya once the periodic marker below has expired, so a new
                                    // mid-stay registration doesn't stay stuck indefinitely. Throttled by
                                    // $periodicRefreshBatchLimit — see comment above where it's defined.
                                    $needsPeriodicRefresh = !$force && !$isNewAdmission
                                        && isset($existingMrnMap[$noRm])
                                        && $periodicRefreshCount < $periodicRefreshBatchLimit
                                        && !\Illuminate\Support\Facades\Cache::has('afya_periodic_synced_' . $noRm);
                                    if ($force || !isset($existingMrnMap[$noRm]) || $isNewAdmission || $needsPeriodicRefresh) {
                                        $patientRmList[] = $noRm;
                                        if ($isNewAdmission) {
                                            \Illuminate\Support\Facades\Cache::forget('afya_reg_details_' . $noRm);
                                        }
                                        if ($needsPeriodicRefresh) {
                                            $periodicRefreshCount++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $patientRmList = array_unique($patientRmList);

            // 2. Pre-fetch registration details from Afya API (outside transaction)
            $patientRegDetails = [];
            if (!empty($patientRmList)) {
                $regService = new \App\Services\AfyaRegistrationService();
                foreach ($patientRmList as $noRm) {
                    $cacheKey = 'afya_reg_details_' . $noRm;
                    
                    if ($force) {
                        try {
                            $regInfo = $regService->getRegistrationDetails($noRm);
                            $cacheData = [
                                'fetched' => true,
                                'registered_date' => $regInfo['registered_date'] ?? null,
                                'dpjp_utama' => $regInfo['dpjp_utama'] ?? null,
                                'tanggal_lahir' => $regInfo['tanggal_lahir'] ?? null
                            ];
                            \Illuminate\Support\Facades\Cache::put($cacheKey, $cacheData, 300); // 5 minutes
                            $patientRegDetails[$noRm] = $cacheData;
                            if (!empty($cacheData['registered_date'])) {
                                // Only mark as periodically synced on a genuinely successful fetch, so a
                                // failed/timed-out attempt gets retried again on the very next cron cycle
                                // instead of waiting out the full refresh interval.
                                \Illuminate\Support\Facades\Cache::put('afya_periodic_synced_' . $noRm, true, now()->addHours($refreshIntervalHours));
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Error calling AfyaRegistrationService for RM: $noRm (forced): " . $e->getMessage());
                        }
                    } else {
                        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                            $patientRegDetails[$noRm] = \Illuminate\Support\Facades\Cache::get($cacheKey);
                        } else {
                            try {
                                $regInfo = $regService->getRegistrationDetails($noRm);
                                $cacheData = [
                                    'fetched' => true,
                                    'registered_date' => $regInfo['registered_date'] ?? null,
                                    'dpjp_utama' => $regInfo['dpjp_utama'] ?? null,
                                    'tanggal_lahir' => $regInfo['tanggal_lahir'] ?? null
                                ];
                                \Illuminate\Support\Facades\Cache::put($cacheKey, $cacheData, 300); // 5 minutes
                                $patientRegDetails[$noRm] = $cacheData;
                                if (!empty($cacheData['registered_date'])) {
                                    // Only mark as periodically synced on a genuinely successful fetch, so a
                                    // failed/timed-out attempt gets retried again on the very next cron cycle
                                    // instead of waiting out the full refresh interval.
                                    \Illuminate\Support\Facades\Cache::put('afya_periodic_synced_' . $noRm, true, now()->addHours($refreshIntervalHours));
                                }
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("Error calling AfyaRegistrationService for RM: $noRm: " . $e->getMessage());
                            }
                        }
                    }
                }
            }

            // 3. Start the transaction only after all network requests are complete
            DB::beginTransaction();

            $activeBedIds = [];

            foreach ($floorsData as $floorData) {
                $floorName = $floorData['floor'];
                if (empty($floorName)) {
                    continue;
                }

                // Find or create Floor
                $floor = Floor::firstOrCreate(['name' => $floorName]);

                foreach ($floorData['wings'] ?? [] as $wingData) {
                    $wingName = $wingData['wing_name'];
                    if (empty($wingName)) {
                        continue;
                    }

                    // Find or create Wing
                    $wing = Wing::firstOrCreate([
                        'floor_id' => $floor->id,
                        'name' => $wingName
                    ]);

                    foreach ($wingData['rooms'] ?? [] as $roomData) {
                        $roomId = $roomData['room_id'];
                        $roomName = $roomData['room_name'];
                        $roomClass = $roomData['class'] ?? null;
                        $totalBeds = $roomData['total_beds'] ?? 0;

                        if (empty($roomId) || empty($roomName)) {
                            continue;
                        }

                        // Update or create Room
                        $room = Room::updateOrCreate(
                            ['id' => $roomId],
                            [
                                'wing_id' => $wing->id,
                                'name' => $roomName,
                                'class' => $roomClass,
                                'total_beds' => $totalBeds
                            ]
                        );

                        foreach ($roomData['beds'] ?? [] as $bedData) {
                            $bedId = $bedData['bed_id'];
                            $bedNumber = $bedData['bed_number'];
                            $bedStatus = $bedData['status'] ?? 'kosong';
                            $isActive = $bedData['is_active'] ?? true;
                            $patientData = $bedData['patient'] ?? null;
                            $futurePatients = $bedData['future_patients'] ?? null;

                            if (empty($bedId) || empty($bedNumber)) {
                                continue;
                            }

                            $activeBedIds[] = $bedId;
                            $equipmentId = null;

                            // If there is patient data, sync to equipments
                            if (!empty($patientData) && !empty($patientData['no_rm'])) {
                                $noRm = trim($patientData['no_rm']);
                                if (strtoupper($noRm) === 'TERDAFTAR') {
                                    $noRm = 'BOOKING-' . $bedId;
                                }
                                $patientName = trim($patientData['name']);
                                $diagnosa = trim($patientData['diagnosa_medis'] ?? '-');
                                $guarantor = trim($patientData['guarantor'] ?? 'UMUM');
                                $age = intval($patientData['age'] ?? 0);

                                // Map guarantor to status_kepemilikan
                                $guarantorUpper = strtoupper($guarantor);
                                if (str_contains($guarantorUpper, 'BPJS')) {
                                    $statusKepemilikan = 'Milik RS';
                                } elseif (str_contains($guarantorUpper, 'ASURANSI') || str_contains($guarantorUpper, 'KSO') || str_contains($guarantorUpper, 'JASA RAHARJA')) {
                                    $statusKepemilikan = 'KSO';
                                } else {
                                    $statusKepemilikan = 'Hibah'; // Umum / Mandiri
                                }

                                // Location mapping compatible with original equipments table
                                $newLocation = $wingName . ' - ' . $roomName . ' (' . $bedNumber . ')';

                                // Extract numeric floor or keep text (e.g. "Perinatologi")
                                $formattedFloor = $floorName;
                                if (preg_match('/Lantai\s+(\d+)/i', $floorName, $matches)) {
                                    $formattedFloor = $matches[1];
                                }

                                // Search for existing patient/equipment
                                $equipment = Equipment::where('serial_number', $noRm)->first();

                                // Read registration details from the pre-fetched local associative array
                                $apiRegDate = null;
                                $apiDpjp = null;
                                $apiTanggalLahir = null;
                                if (strpos($noRm, 'BOOKING-') !== 0 && isset($patientRegDetails[$noRm])) {
                                    $apiRegDate = $patientRegDetails[$noRm]['registered_date'] ?? null;
                                    $apiDpjp = $patientRegDetails[$noRm]['dpjp_utama'] ?? null;
                                    $apiTanggalLahir = $patientRegDetails[$noRm]['tanggal_lahir'] ?? null;
                                }

                                // Prioritize the direct date_of_birth from the bed-monitoring API payload
                                if (!empty($patientData['date_of_birth'])) {
                                    try {
                                        $apiTanggalLahir = date('Y-m-d', strtotime($patientData['date_of_birth']));
                                    } catch (\Exception $e) {}
                                }

                                $apiRencanaPulang = $patientData['rencana_pulang'] ?? $patientData['estimasi_pulang'] ?? $patientData['estimated_discharge'] ?? $patientData['discharge_date'] ?? $patientData['tgl_pulang'] ?? null;

                                if ($equipment) {
                                    $oldLocation = $equipment->lokasi;

                                    // Update details
                                    $updateData = [
                                        'merk' => $patientName,
                                        'type' => $diagnosa,
                                        'lokasi' => $newLocation,
                                        'lantai' => $formattedFloor,
                                        'status_kepemilikan' => $statusKepemilikan,
                                        'gender' => $patientData['gender'] ?? null,
                                        'guarantor' => $patientData['guarantor'] ?? null,
                                        'hak_kelas' => $roomClass,
                                        'registered_date' => $apiRegDate ?: ($equipment->registered_date ?: now()->format('Y-m-d')),
                                    ];
                                    if ($apiRencanaPulang) {
                                        $updateData['rencana_pulang'] = $apiRencanaPulang;
                                    }
                                    if ($apiDpjp) {
                                        $updateData['dpjp_utama'] = $apiDpjp;
                                    }
                                    if ($apiTanggalLahir) {
                                        $updateData['tanggal_lahir'] = $apiTanggalLahir;
                                    }
                                    $equipment->update($updateData);

                                    // Detect movement/transfer to a different bed/room
                                    if ($oldLocation !== $newLocation) {
                                        Maintenance::create([
                                            'equipment_id' => $equipment->id,
                                            'jenis_pemeliharaan' => 'Pemindahan Ruang Rawat',
                                            'tanggal_pelaksanaan' => now()->format('Y-m-d'),
                                            'tanggal_jadwal_berikutnya' => now()->format('Y-m-d'),
                                            'tindakan_hasil' => "Rujukan Internal: Pasien dipindahkan dari bed/kamar lama [{$oldLocation}] menuju bed/kamar baru [{$newLocation}] via sinkronisasi otomatis.",
                                            'petugas' => 'Sistem Bed Monitoring',
                                            'diagnosa_gejala' => $equipment->type,
                                            'lokasi_rawat' => $newLocation,
                                            'kondisi_klinis' => $equipment->kondisi,
                                            'metode_pembayaran' => $equipment->status_kepemilikan,
                                        ]);
                                    }
                                } else {
                                    // Calculate estimated birthdate from age
                                    $estimatedBirthdate = Carbon::now()->subYears($age)->startOfYear()->format('Y-m-d');

                                    // Create new patient
                                    $createData = [
                                        'merk' => $patientName,
                                        'type' => $diagnosa,
                                        'serial_number' => $noRm,
                                        'tanggal_lahir' => $apiTanggalLahir ?: $estimatedBirthdate,
                                        'lokasi' => $newLocation,
                                        'lantai' => $formattedFloor,
                                        'kondisi' => 'Stabil EWS',
                                        'spesifikasi' => null,
                                        'tanggal_pengadaan' => now()->format('Y-m-d'),
                                        'jam' => now()->format('H:i'),
                                        'status_kepemilikan' => $statusKepemilikan,
                                        'gender' => $patientData['gender'] ?? null,
                                        'guarantor' => $patientData['guarantor'] ?? null,
                                        'hak_kelas' => $roomClass,
                                        'registered_date' => $apiRegDate ?: now()->format('Y-m-d'),
                                    ];
                                    if ($apiRencanaPulang) {
                                        $createData['rencana_pulang'] = $apiRencanaPulang;
                                    }
                                    if ($apiDpjp) {
                                        $createData['dpjp_utama'] = $apiDpjp;
                                    }
                                    $equipment = Equipment::create($createData);
                                }

                                $equipmentId = $equipment->id;
                            }

                            // Update or create Bed
                             Bed::updateOrCreate(
                                 ['id' => $bedId],
                                 [
                                     'room_id' => $room->id,
                                     'bed_number' => $bedNumber,
                                     'status' => $bedStatus,
                                     'is_active' => $isActive,
                                     'equipment_id' => $equipmentId,
                                     'future_patients' => $futurePatients
                                 ]
                             );
                        }
                    }
                }
            }

            // Remove patient associations from beds that are no longer in the current sync loop (if any)
            if (!empty($activeBedIds)) {
                // Record discharge time for patients whose bed is being vacated right now
                $dischargedEquipment = Equipment::whereIn('id',
                    Bed::whereNotIn('id', $activeBedIds)
                        ->where('status', '!=', 'kosong')
                        ->whereNotNull('equipment_id')
                        ->pluck('equipment_id')
                )->get(['id', 'serial_number']);

                // BOOKING-{bed_id} is a per-bed reservation placeholder, not a real patient (see where
                // it's synthesized above, ~line 260) — it disappearing from the live feed means the
                // booking was cancelled, or it was fulfilled and replaced by a real RM, never that
                // someone was "discharged". Counting it as pulang inflates the discharged-patient list
                // with phantom entries, so these are deleted outright instead of marked as pulang.
                $bookingIds = $dischargedEquipment->filter(fn($e) => str_starts_with($e->serial_number, 'BOOKING-'))->pluck('id');
                $realPatientIds = $dischargedEquipment->reject(fn($e) => str_starts_with($e->serial_number, 'BOOKING-'))->pluck('id');

                if ($bookingIds->isNotEmpty()) {
                    Equipment::whereIn('id', $bookingIds)->delete();
                }

                if ($realPatientIds->isNotEmpty()) {
                    // Don't overwrite a waktu_pulang that's already set — sync:discharged-patients may
                    // have already filled it in from the bed-monitoring discharged-patients API, which
                    // carries the actual discharge time. That's more accurate than "now()" (merely the
                    // moment this sync noticed the bed was no longer occupied), so once it's set, leave
                    // it alone; only backfill it here as a fallback for patients that source doesn't
                    // (yet) know about.
                    Equipment::whereIn('id', $realPatientIds)
                        ->whereNull('waktu_pulang') // waktu_pulang is a timestamp column — only NULL means "not set yet"
                        ->update([
                            'waktu_pulang' => now()
                        ]);
                }

                Bed::whereNotIn('id', $activeBedIds)->update([
                    'status' => 'kosong',
                    'equipment_id' => null
                ]);
            }

            DB::commit();
            $this->info('Bed synchronization completed successfully.');
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error during bed synchronization: ' . $e->getMessage());
            return 1;
        }
    }
}
