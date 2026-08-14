<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Equipment;

class SyncDischargedPatients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:discharged-patients';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enrich waktu_pulang using the bed-monitoring discharged-patients API (authoritative discharge time), '
        . 'instead of relying only on sync:beds noticing a patient disappeared from the occupancy feed. '
        . 'Only fills in patients that do not already have waktu_pulang set — never overwrites existing data.';

    public function handle()
    {
        $this->info('Starting discharged-patients sync...');

        try {
            $response = Http::withoutVerifying()
                ->withToken('rsui_bed_mon_secret_key_2026')
                ->timeout(15)
                ->get('https://bed-monitoring.rs.ui.ac.id/api/external/discharged-patients');

            if (!$response->successful()) {
                $this->error('Failed to fetch discharged-patients API. HTTP Status: ' . $response->status());
                return 1;
            }

            $apiRaw = $response->json();
            $apiData = collect($apiRaw['data'] ?? $apiRaw);

            if ($apiData->isEmpty()) {
                $this->info('No discharged patients returned by the API.');
                return 0;
            }

            // The API can list the same no_rm more than once (e.g. multiple discharge episodes in its
            // window) — keep only the most recent waktu_pulang per RM.
            $latestByRm = $apiData
                ->filter(fn($p) => !empty($p['no_rm']))
                ->groupBy(fn($p) => trim($p['no_rm']))
                ->map(fn($group) => $group->sortByDesc('waktu_pulang')->first());

            $created = 0;
            $enriched = 0;
            $skippedExisting = 0;

            foreach ($latestByRm as $noRm => $p) {
                $equipment = Equipment::where('serial_number', $noRm)->first();

                if (!$equipment) {
                    // Never tracked locally at all — most likely admitted and discharged between two
                    // 5-minute bed-occupancy sync cycles, so sync:beds never had a chance to create a
                    // record for them. Create one directly from the authoritative discharge data so
                    // they aren't missing from Riwayat Pasien entirely.
                    $roomName = $p['room_name'] ?? null;
                    $bedNumber = $p['bed_number'] ?? null;
                    $lokasi = $roomName ? ($roomName . ($bedNumber ? " ({$bedNumber})" : '')) : '-';

                    $floorName = $p['floor'] ?? null;
                    $lantai = $floorName;
                    if ($floorName && preg_match('/Lantai\s+(\d+)/i', $floorName, $matches)) {
                        $lantai = $matches[1];
                    }

                    Equipment::create([
                        'serial_number'      => $noRm,
                        'merk'               => $p['name'] ?? '-',
                        'type'               => $p['diagnosa_medis'] ?? '-',
                        'lokasi'             => $lokasi,
                        'lantai'             => $lantai,
                        'tanggal_pengadaan'  => now()->format('Y-m-d'), // no admission date available from this API
                        'gender'             => $p['gender'] ?? null,
                        'tanggal_lahir'      => $p['date_of_birth'] ?? null,
                        'guarantor'          => $p['guarantor'] ?? null,
                        'hak_kelas'          => $p['hak_kelas'] ?? null,
                        'dpjp_utama'         => $p['dpjp'] ?? null,
                        'waktu_pulang'       => $p['waktu_pulang'] ?? null,
                    ]);
                    $created++;
                    continue;
                }

                // Existing data is left untouched — only fill in waktu_pulang when it's genuinely
                // missing, never overwrite a value that's already there.
                if (!empty($equipment->waktu_pulang)) {
                    $skippedExisting++;
                    continue;
                }

                $equipment->update([
                    'waktu_pulang' => $p['waktu_pulang'] ?? now(),
                ]);
                $enriched++;

                // Deliberately NOT touching the bed relation here. The discharged-patients and
                // beds-occupancy feeds on the bed-monitoring side can disagree for a long time (seen
                // in practice: a patient still listed as occupying a bed by beds-occupancy over 24h
                // after discharged-patients already reported them discharged). sync:beds trusts
                // beds-occupancy and will re-assert the bed relation on its very next 5-minute cycle,
                // so forcing a release here just causes the patient to flicker between Riwayat Pasien
                // and Pasien Sudah Pulang for no benefit. beds-occupancy (via sync:beds) stays the
                // single source of truth for "is this patient currently in a bed" — this command only
                // supplies a more accurate waktu_pulang once sync:beds does release them.
            }

            $this->info("Selesai. Baru dibuat: {$created}, waktu_pulang terisi: {$enriched}, sudah ada data (dilewati): {$skippedExisting}.");
            return 0;
        } catch (\Exception $e) {
            Log::error('SyncDischargedPatients error: ' . $e->getMessage());
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
