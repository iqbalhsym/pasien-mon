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

        // Get all floors with their wings, rooms, and beds to calculate floor-specific statistics
        $floors = Floor::with(['wings.rooms.beds'])->get()->sortBy(function ($floor) {
            if (is_numeric($floor->name)) {
                return (int)$floor->name;
            }
            return 1000 + ord($floor->name[0] ?? '');
        });

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
        $selectedFloorName = $request->input('floor');
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
                ->orderBy('name', 'asc')
                ->get();
        }

        // Calculate global statistics (TOTAL TEMPAT TIDUR hanya yang aktif, BED KOSONG hanya yang kosong tapi aktif)
        $totalBeds = Bed::where('is_active', true)->count();
        $occupiedBeds = Bed::where('status', 'terisi')->where('is_active', true)->count();
        $vacantBeds = Bed::where('status', 'kosong')->where('is_active', true)->count();
        $cleaningBeds = Bed::where('status', 'cleaning')->where('is_active', true)->count();
        $bookedBeds = Bed::where('status', 'booking')->where('is_active', true)->count();
        $inactiveBeds = Bed::where('is_active', false)->count();

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

        try {
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, 30);
            Artisan::call('sync:beds', ['--force' => true]);
            return response()->json([
                'success' => true,
                'message' => 'Sinkronisasi data tempat tidur berhasil diselesaikan!'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan sinkronisasi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateNurses(\Illuminate\Http\Request $request, $equipmentId)
    {
        $request->validate([
            'ners_pagi' => 'nullable|string|max:255',
            'ners_siang' => 'nullable|string|max:255',
            'ners_malam' => 'nullable|string|max:255',
        ]);

        $equipment = \App\Models\Equipment::findOrFail($equipmentId);
        $equipment->update([
            'ners_pagi' => $request->ners_pagi,
            'ners_siang' => $request->ners_siang,
            'ners_malam' => $request->ners_malam,
        ]);

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
