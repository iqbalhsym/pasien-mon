<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = Equipment::withCount('maintenances')
            ->with(['maintenances' => function($q) {
                $q->latest('tanggal_pelaksanaan');
            }])
            ->orderBy('merk');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('merk', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('lokasi', 'like', "%{$search}%");
            });
        }

        $equipmentsPaginator = $query->paginate(10);
        $equipments = Equipment::all(); // untuk modal daftar pilihan dropdown
        
        return view('maintenances.index', compact('equipmentsPaginator', 'equipments', 'search'));
    }

    public function history(Request $request, $serial_number)
    {
        $equipment = Equipment::where('serial_number', $serial_number)->firstOrFail();
        
        $maintenances = Maintenance::where('equipment_id', $equipment->id)
            ->latest('tanggal_pelaksanaan')
            ->paginate(10);
            
        $equipments = Equipment::all(); // untuk modal Tambah (jika user mau ganti alat)

        return view('maintenances.history', compact('equipment', 'maintenances', 'equipments'));
    }

    public function publicHistory(Request $request, $serial_number)
    {
        $equipment = Equipment::where('serial_number', $serial_number)->firstOrFail();
        
        if ($equipment->tanggal_lahir) {
            $sessionKey = 'verified_patient_' . $equipment->id;
            
            if ($request->isMethod('post')) {
                $request->validate([
                    'tanggal_lahir' => 'required|date'
                ]);
                
                if ($request->tanggal_lahir === $equipment->tanggal_lahir) {
                    session([$sessionKey => true]);
                    return redirect()->route('alat.public', $serial_number);
                }
                
                return back()->withErrors(['tanggal_lahir' => 'Tanggal lahir salah. Akses ditolak!']);
            }
            
            if (!session($sessionKey)) {
                return view('maintenances.verify', compact('equipment'));
            }
        }
        
        $maintenances = Maintenance::where('equipment_id', $equipment->id)
            ->latest('tanggal_pelaksanaan')
            ->get(); // Tanpa paginasi untuk tampilan publik yang simpel jika diinginkan, atau pakai paginate.
            
        return view('maintenances.public', compact('equipment', 'maintenances'));
    }

    public function printQr($serial_number)
    {
        $equipment = Equipment::where('serial_number', $serial_number)->firstOrFail();
        $url = route('alat.public', $equipment->serial_number);
        
        return view('maintenances.qrcode', compact('equipment', 'url'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'equipment_id' => 'required|exists:equipments,id',
            'jenis_pemeliharaan' => 'required|in:Preventif,Pemindahan Poli,Korektif',
            'tanggal_pelaksanaan' => 'required|date',
            'tanggal_jadwal_berikutnya' => 'required|date|after:tanggal_pelaksanaan',
            'tindakan_hasil' => 'required',
            'petugas' => 'required',
            'diagnosa_gejala' => 'nullable|string',
            'lokasi_rawat' => 'nullable|string',
            'kondisi_klinis' => 'nullable|string',
            'metode_pembayaran' => 'nullable|string'
        ]);

        Maintenance::create($request->all());

        return redirect()->route('maintenances.index')->with('success', 'Data riwayat pasien berhasil dicatat!');
    }

    public function update(Request $request, $id)
    {
        $maintenance = Maintenance::findOrFail($id);

        $request->validate([
            'equipment_id' => 'required|exists:equipments,id',
            'jenis_pemeliharaan' => 'required|in:Preventif,Pemindahan Poli,Korektif',
            'tanggal_pelaksanaan' => 'required|date',
            'tanggal_jadwal_berikutnya' => 'required|date|after:tanggal_pelaksanaan',
            'tindakan_hasil' => 'required',
            'petugas' => 'required',
            'diagnosa_gejala' => 'nullable|string',
            'lokasi_rawat' => 'nullable|string',
            'kondisi_klinis' => 'nullable|string',
            'metode_pembayaran' => 'nullable|string'
        ]);

        $maintenance->update($request->all());

        return redirect()->route('maintenances.index')->with('success', 'Data riwayat pasien berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $maintenance = Maintenance::findOrFail($id);
        $maintenance->delete();

        return redirect()->route('maintenances.index')->with('success', 'Data pemeliharaan berhasil dihapus!');
    }

    public function exportCSV()
    {
        $maintenances = Maintenance::with('equipment')->get();
        $filename = "laporan_pemeliharaan_alat_" . date('Y-m-d') . ".csv";

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = [
            'No', 'Alat', 'No Seri', 'Lokasi', 'Jenis Pemeliharaan', 
            'Tanggal Pelaksanaan', 'Tanggal Berikutnya', 'Tindakan/Hasil', 'Petugas'
        ];

        $callback = function() use($maintenances, $columns) {
            // Write to output stream via standard output buffer
            $file = fopen('php://output', 'w');
            
            // Output BOM for Excel UTF-8 support
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, $columns, ';'); // Using Semicolon for Indonesian Excel standard

            foreach ($maintenances as $key => $m) {
                fputcsv($file, [
                    $key + 1,
                    $m->equipment->merk . ' ' . $m->equipment->type,
                    $m->equipment->serial_number,
                    $m->equipment->lokasi,
                    $m->jenis_pemeliharaan,
                    $m->tanggal_pelaksanaan,
                    $m->tanggal_jadwal_berikutnya,
                    $m->tindakan_hasil,
                    $m->petugas
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
