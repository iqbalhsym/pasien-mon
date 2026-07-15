<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Floor;
use App\Models\Wing;
use App\Models\Room;
use App\Models\Bed;
use App\Models\Nurse;
use Illuminate\Support\Facades\Artisan;

class BedController extends Controller
{
    public function index(Request $request)
    {
        $activeNurses = Nurse::where('is_active', true)->orderBy('name', 'asc')->get();

        $userFloor = (auth()->check() && auth()->user()->floor && auth()->user()->role !== 'admin') ? auth()->user()->floor : null;

        // Get all floors with their wings, rooms, and beds to calculate floor-specific statistics
        $floors = Floor::with(['wings.rooms.beds'])->get()->sortBy(function ($floor) {
            if (is_numeric($floor->name)) {
                return (int)$floor->name;
            }
            return 1000 + ord($floor->name[0] ?? '');
        });

        if ($userFloor) {
            $floors = $floors->filter(function ($floor) use ($userFloor) {
                $flName = $floor->name;
                if (preg_match('/Lantai\s+(\d+)/i', $flName, $matches)) {
                    $flName = $matches[1];
                }
                return strtolower(trim($flName)) === strtolower(trim($userFloor));
            });
        }

        // Calculate statistics for each floor
        foreach ($floors as $floor) {
            $beds = $floor->wings->flatMap(function ($wing) {
                return $wing->rooms->flatMap(function ($room) {
                    return $room->beds;
                });
            });

            $activeBeds = $beds->where('is_active', true);
            $floor->total_active_beds = $activeBeds->count();
            $floor->occupied_beds = $activeBeds->where('status', 'terisi')->count();
            $floor->vacant_beds = $activeBeds->where('status', 'kosong')->count();
            $floor->cleaning_beds = $activeBeds->where('status', 'cleaning')->count();
            $floor->booked_beds = $activeBeds->where('status', 'booking')->count();
            $floor->inactive_beds = $beds->where('is_active', false)->count();
            $floor->occupancy_rate = $floor->total_active_beds > 0 
                ? round(($floor->occupied_beds / $floor->total_active_beds) * 100, 1) 
                : 0;
        }

        // Get selected floor, default to first floor if not provided
        if ($userFloor) {
            $selectedFloorName = $userFloor;
        } else {
            $selectedFloorName = $request->input('floor');
        }

        $selectedFloor = null;
        if ($selectedFloorName) {
            $selectedFloor = $floors->first(function ($fl) use ($selectedFloorName) {
                return $fl->name === $selectedFloorName || $fl->name === 'Lantai ' . $selectedFloorName;
            });
        }
        if (!$selectedFloor && $floors->isNotEmpty()) {
            $selectedFloor = $floors->first();
        }
        
        $selectedFloorName = $selectedFloor ? $selectedFloor->name : null;

        // Load wings, rooms, and beds for the selected floor's detailed view
        $wings = collect();
        if ($selectedFloor) {
            $wingsQuery = Wing::where('floor_id', $selectedFloor->id);

            if ($request->filled('wing')) {
                $wingsQuery->where('name', $request->input('wing'));
            }

            $wings = $wingsQuery->with(['rooms' => function ($q) use ($request) {
                    $q->orderBy('name', 'asc');
                    if ($request->filled('room')) {
                        $q->where('name', $request->input('room'));
                    }
                }, 'rooms.beds' => function ($q) {
                    $q->orderBy('bed_number', 'asc');
                }, 'rooms.beds.equipment'])
                ->orderByRaw("CASE WHEN UPPER(name) = 'WIJAYA KUSUMA' THEN 0 WHEN UPPER(name) = 'TAPAK DARA' THEN 1 ELSE 2 END")
                ->orderBy('name', 'asc')
                ->get();
        }

        // Calculate global statistics (TOTAL TEMPAT TIDUR hanya yang aktif, BED KOSONG hanya yang kosong tapi aktif)
        $statQuery = Bed::where('is_active', true);
        $inactiveQuery = Bed::where('is_active', false);
        if ($userFloor) {
            $statQuery->whereHas('room.wing.floor', function($q) use ($userFloor) {
                $q->where('name', $userFloor)->orWhere('name', 'Lantai ' . $userFloor);
            });
            $inactiveQuery->whereHas('room.wing.floor', function($q) use ($userFloor) {
                $q->where('name', $userFloor)->orWhere('name', 'Lantai ' . $userFloor);
            });
        }

        $totalBeds = $statQuery->count();
        $occupiedBeds = (clone $statQuery)->where('status', 'terisi')->count();
        $vacantBeds = (clone $statQuery)->where('status', 'kosong')->count();
        $cleaningBeds = (clone $statQuery)->where('status', 'cleaning')->count();
        $bookedBeds = (clone $statQuery)->where('status', 'booking')->count();
        $inactiveBeds = $inactiveQuery->count();

        $occupancyRate = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100, 1) : 0;

        return view('beds.index', compact(
            'floors',
            'selectedFloorName',
            'selectedFloor',
            'wings',
            'totalBeds',
            'occupiedBeds',
            'vacantBeds',
            'cleaningBeds',
            'bookedBeds',
            'inactiveBeds',
            'occupancyRate',
            'activeNurses'
        ));
    }

    public function sync()
    {
        $cacheKey = 'last_sync_api_trigger';
        if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
            return response()->json([
                'success' => true,
                'message' => 'Sinkronisasi dilewati karena baru saja disinkronkan.'
            ]);
        }

        // Tandai cache SEBELUM menjalankan proses, agar request berikutnya skip
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, 60);

        // Jalankan sync:beds di background process yang terpisah dari HTTP worker ini.
        // Dengan ini, worker PHP-FPM langsung bebas dan tidak memblokir request lain.
        $phpBinary  = PHP_BINARY;
        $artisanPath = base_path('artisan');
        $logPath     = storage_path('logs/sync-beds-bg.log');

        $cmd = sprintf(
            '%s %s sync:beds --force >> %s 2>&1 &',
            escapeshellarg($phpBinary),
            escapeshellarg($artisanPath),
            escapeshellarg($logPath)
        );

        // exec dengan & agar proses terpisah dan tidak ditunggu
        exec($cmd);

        return response()->json([
            'success' => true,
            'message' => 'Sinkronisasi data tempat tidur sedang diproses di background.'
        ]);
    }

    public function updateNurses(\Illuminate\Http\Request $request, $equipmentId)
    {
        $request->validate([
            'ners_pagi' => 'nullable|string|max:255',
            'ners_siang' => 'nullable|string|max:255',
            'ners_malam' => 'nullable|string|max:255',
        ]);

        $equipment = \App\Models\Equipment::findOrFail($equipmentId);
        $oldPagi = $equipment->ners_pagi;
        $oldSiang = $equipment->ners_siang;
        $oldMalam = $equipment->ners_malam;

        $equipment->update([
            'ners_pagi' => $request->ners_pagi,
            'ners_siang' => $request->ners_siang,
            'ners_malam' => $request->ners_malam,
        ]);

        // Log to Maintenance for history
        $shifts = [
            'Pagi' => ['old' => $oldPagi, 'new' => $request->ners_pagi],
            'Siang' => ['old' => $oldSiang, 'new' => $request->ners_siang],
            'Malam' => ['old' => $oldMalam, 'new' => $request->ners_malam],
        ];

        foreach ($shifts as $shiftName => $names) {
            $newNers = trim($names['new']);
            if (!empty($newNers) && $newNers !== '-' && $newNers !== trim($names['old'])) {
                \App\Models\Maintenance::create([
                    'equipment_id' => $equipment->id,
                    'jenis_pemeliharaan' => 'Penugasan Ners',
                    'tanggal_pelaksanaan' => now()->format('Y-m-d'),
                    'tanggal_jadwal_berikutnya' => now()->format('Y-m-d'),
                    'tindakan_hasil' => "Ditugaskan sebagai Ners {$shiftName} untuk pasien {$equipment->merk}.",
                    'petugas' => $newNers, // The nurse name
                    'diagnosa_gejala' => $equipment->type,
                    'lokasi_rawat' => $equipment->lokasi,
                    'kondisi_klinis' => $equipment->kondisi ?: 'Stabil EWS',
                    'metode_pembayaran' => $equipment->status_kepemilikan ?: 'Milik RS',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Data ners bertugas berhasil diperbarui!'
        ]);
    }

    public function updateEws(\Illuminate\Http\Request $request, $equipmentId)
    {
        $request->validate([
            'ews' => 'nullable|string|max:255',
        ]);

        $equipment = \App\Models\Equipment::findOrFail($equipmentId);
        $equipment->update([
            'ews' => $request->ews,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status EWS pasien berhasil diperbarui!'
        ]);
    }
}
