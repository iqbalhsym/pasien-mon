<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class MaintenanceController extends Controller
{
    private function fetchApiPatientsMap()
    {
        return \Illuminate\Support\Facades\Cache::remember('api_patients_map', 300, function () {
            try {
                $apiUrl = 'https://bed-monitoring.rs.ui.ac.id/api/external/beds-occupancy';
                $apiKey = 'rsui_bed_mon_secret_key_2026';
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'X-API-Key' => $apiKey,
                    'Content-Type: application/json'
                ])->timeout(10)->get($apiUrl);

                if ($response->successful()) {
                    $body = $response->json();
                    $map = [];
                    if (isset($body['data']) && is_array($body['data'])) {
                        foreach ($body['data'] as $floor) {
                            foreach ($floor['wings'] ?? [] as $wing) {
                                foreach ($wing['rooms'] ?? [] as $room) {
                                    $roomClass = $room['class'] ?? '-';
                                    foreach ($room['beds'] ?? [] as $bed) {
                                        $patient = $bed['patient'] ?? null;
                                        if ($patient && !empty($patient['no_rm'])) {
                                            $noRm = trim($patient['no_rm']);
                                            $map[$noRm] = [
                                                'gender' => $patient['gender'] ?? '-',
                                                'guarantor' => $patient['guarantor'] ?? '-',
                                                'class' => $roomClass,
                                                'diagnosa_medis' => $patient['diagnosa_medis'] ?? '-',
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                    return $map;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Gagal fetch api_patients_map: ' . $e->getMessage());
            }
            return [];
        });
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        $sort = $request->input('sort', 'terbaru');

        $query = Equipment::withCount('maintenances')
            ->with(['maintenances' => function($q) {
                $q->latest('tanggal_pelaksanaan');
            }]);

        if ($sort === 'alphabetical') {
            $query->orderBy('merk', 'asc');
        } elseif ($sort === 'alphabetical_desc') {
            $query->orderBy('merk', 'desc');
        } else {
            $query->orderBy('id', 'desc');
        }

        if ($request->filled('lantai')) {
            $lantaiVal = $request->input('lantai');
            if (preg_match('/Lantai\s+(\d+)/i', $lantaiVal, $matches)) {
                $lantaiVal = $matches[1];
            }
            $query->where('lantai', $lantaiVal);
        }

        if ($request->filled('wing')) {
            $wingVal = $request->input('wing');
            $query->where(function($q) use ($wingVal) {
                $q->whereHas('bed.room.wing', function($wq) use ($wingVal) {
                    $wq->where('name', $wingVal);
                })->orWhere('lokasi', 'like', $wingVal . ' - %');
            });
        }

        if ($request->filled('room')) {
            $roomVal = $request->input('room');
            $query->where(function($q) use ($roomVal) {
                $q->whereHas('bed.room', function($rq) use ($roomVal) {
                    $rq->where('name', $roomVal);
                })->orWhere('lokasi', 'like', '% - ' . $roomVal . ' (%');
            });
        }

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
        
        $patientsMap = $this->fetchApiPatientsMap();
        
        return view('maintenances.index', compact('equipmentsPaginator', 'equipments', 'search', 'sort', 'patientsMap'));
    }

    public function patientDetail(Request $request, $serial_number)
    {
        $equipment = Equipment::where('serial_number', $serial_number)->firstOrFail();
        $patientsMap = $this->fetchApiPatientsMap();
        $apiData = $patientsMap[$equipment->serial_number] ?? null;

        return view('maintenances.patient_detail', compact('equipment', 'apiData'));
    }

    public function updatePatientDetail(Request $request, $serial_number)
    {
        $equipment = Equipment::where('serial_number', $serial_number)->firstOrFail();

        $request->validate([
            'registered_date' => 'nullable|string',
            'los_aktual' => 'nullable|string',
            'dpjp_utama' => 'nullable|string',
            'dpjp_raber' => 'nullable|string',
            'dokter_konsul' => 'nullable|string',
            'visit_dpjp' => 'nullable|string',
            'planning_pasien' => 'nullable|string',
            'rencana_pulang' => 'nullable|string',
            'npja' => 'nullable|string',
            'ews' => 'nullable|string',
            'tingkat_ketergantungan' => 'nullable|string',
            'ners_bertugas' => 'nullable|string',
            'alkes_invasif' => 'nullable|string',
            'tindakan_detail' => 'nullable|string',
        ]);

        $equipment->update($request->all());

        return redirect()->route('maintenances.patient_detail', $serial_number)
            ->with('success', 'Detail informasi klinis pasien berhasil diperbarui!');
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
