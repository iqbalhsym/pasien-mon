<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Floor;
use App\Models\Wing;
use App\Models\Room;
use App\Models\Bed;
use Illuminate\Support\Facades\Artisan;

class BedController extends Controller
{
    public function index(Request $request)
    {
        // Get all floors sorted correctly
        $floors = Floor::all()->sortBy(function ($floor) {
            if (is_numeric($floor->name)) {
                return (int)$floor->name;
            }
            return 1000 + ord($floor->name[0] ?? '');
        });

        // Get selected floor, default to first floor if not provided
        $selectedFloorName = $request->input('floor');
        if ($selectedFloorName) {
            $matchedFloor = Floor::where('name', $selectedFloorName)
                ->orWhere('name', 'Lantai ' . $selectedFloorName)
                ->first();
            if ($matchedFloor) {
                $selectedFloorName = $matchedFloor->name;
            }
        }
        if (!$selectedFloorName && $floors->isNotEmpty()) {
            $selectedFloorName = $floors->first()->name;
        }

        $selectedFloor = Floor::where('name', $selectedFloorName)->first();

        // Load wings, rooms, and beds for the selected floor
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

        // Calculate global statistics
        $totalBeds = Bed::count();
        $occupiedBeds = Bed::where('status', 'terisi')->count();
        $vacantBeds = Bed::where('status', 'kosong')->count();
        $cleaningBeds = Bed::where('status', 'cleaning')->count();
        $inactiveBeds = Bed::where('is_active', false)->count();

        // Active beds are those that are marked active in the system
        $activeBeds = Bed::where('is_active', true)->count();
        $occupancyRate = $activeBeds > 0 ? round(($occupiedBeds / $activeBeds) * 100, 1) : 0;

        return view('beds.index', compact(
            'floors',
            'selectedFloorName',
            'selectedFloor',
            'wings',
            'totalBeds',
            'occupiedBeds',
            'vacantBeds',
            'cleaningBeds',
            'inactiveBeds',
            'occupancyRate'
        ));
    }

    public function sync()
    {
        try {
            Artisan::call('sync:beds');
            return response()->json([
                'success' => true,
                'message' => 'Sinkronisasi data tempat tidur berhasil diselesaikan!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan sinkronisasi: ' . $e->getMessage()
            ], 500);
        }
    }
}
