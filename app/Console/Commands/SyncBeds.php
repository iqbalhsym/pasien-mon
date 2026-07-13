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

            // Get already cached/populated patient details from the local database
            $existingMrnMap = [];
            if (!$force) {
                $existingMrnMap = Equipment::whereNotNull('registered_date')
                    ->where('registered_date', '!=', '')
                    ->whereNotNull('dpjp_utama')
                    ->where('dpjp_utama', '!=', '')
                    ->pluck('serial_number')
                    ->toArray();
                $existingMrnMap = array_flip($existingMrnMap);
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
                                    if ($force || !isset($existingMrnMap[$noRm])) {
                                        $patientRmList[] = $noRm;
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
                                    'equipment_id' => $equipmentId
                                ]
                            );
                        }
                    }
                }
            }

            // Remove patient associations from beds that are no longer in the current sync loop (if any)
            if (!empty($activeBedIds)) {
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
