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
        $alatBaik = Equipment::whereIn('kondisi', ['Baik', 'Stabil EWS'])->count();
        $alatRusakRingan = Equipment::whereIn('kondisi', ['Rusak Ringan', 'Stabil perlu observasi rutin EWS', 'Perlu pemantauan khusus EWS'])->count();
        $alatOrange = Equipment::where('kondisi', 'Perlu pemantauan ketat EWS')->count();
        $alatRusakBerat = Equipment::whereIn('kondisi', ['Rusak Berat', 'Intensif ESW', 'Intensif EWS'])->count();

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
            'totalAlat', 'alatBaik', 'alatRusakRingan', 'alatOrange', 'alatRusakBerat',
            'nearCalibrationAlat', 'nearMaintenanceAlat', 'kso', 'milikRS', 'hibah'
        ));
    }
}