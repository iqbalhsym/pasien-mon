<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Equipment;
use App\Models\Calibration;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalAlat = Equipment::count();
        $alatBaik = Equipment::where('kondisi', 'Baik')->count();
        $alatRusakRingan = Equipment::where('kondisi', 'Rusak Ringan')->count();
        $alatRusakBerat = Equipment::where('kondisi', 'Rusak Berat')->count();

        // Calculate approaching calibrations (next 1 year or overdue)
        $today = Carbon::today();
        $warningDateCal = Carbon::today()->addYears(1);
        $warningDateMnt = Carbon::today()->addMonths(1);

        $nearCalibrationAlat = Calibration::whereBetween('tanggal_kalibrasi_berikutnya', [$today, $warningDateCal])
                                    ->orWhere('tanggal_kalibrasi_berikutnya', '<', $today)
                                    ->with('equipment')
                                    ->get();

        $nearMaintenanceAlat = \App\Models\Maintenance::whereBetween('tanggal_jadwal_berikutnya', [$today, $warningDateMnt])
                                    ->orWhere('tanggal_jadwal_berikutnya', '<', $today)
                                    ->with('equipment')
                                    ->get();

        $kso = Equipment::where('status_kepemilikan', 'KSO')->count();
        $milikRS = Equipment::where('status_kepemilikan', 'Milik RS')->count();
        $hibah = Equipment::where('status_kepemilikan', 'Hibah')->count();

        return view('dashboard', compact(
            'totalAlat', 'alatBaik', 'alatRusakRingan', 'alatRusakBerat',
            'nearCalibrationAlat', 'nearMaintenanceAlat', 'kso', 'milikRS', 'hibah'
        ));
    }
}