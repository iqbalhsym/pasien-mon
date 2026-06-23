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
                                                'rencana_pulang' => $patient['rencana_pulang'] ?? $patient['estimasi_pulang'] ?? $patient['estimated_discharge'] ?? $patient['discharge_date'] ?? $patient['tgl_pulang'] ?? null,
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
            ->with(['media', 'maintenances' => function($q) {
                $q->latest('tanggal_pelaksanaan');
            }]);

        if ($sort === 'alphabetical') {
            $query->orderBy('merk', 'asc');
        } elseif ($sort === 'alphabetical_desc') {
            $query->orderBy('merk', 'desc');
        } elseif ($sort === 'ruangan') {
            $query->orderBy('lokasi', 'asc')->orderBy('lantai', 'asc');
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

        if ($request->filled('filter_ruangan')) {
            $ruanganVal = $request->input('filter_ruangan');
            $query->where(function($q) use ($ruanganVal) {
                $q->where('lokasi', 'like', "%{$ruanganVal}%")
                  ->orWhere('lantai', 'like', "%{$ruanganVal}%");
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

        $perPage = $request->input('per_page', 10);

        if ($sort === 'los_terlama' || $sort === 'los_singkat') {
            // Ambil semua data sesuai filter (tanpa paginasi sql)
            $allEquipments = $query->get();

            // Hitung LOS secara manual di PHP
            foreach ($allEquipments as $eq) {
                $tglMasukRaw = $eq->registered_date ?: $eq->tanggal_pengadaan;
                $tglMasukParsed = null;
                try {
                    $tglMasukParsed = \Carbon\Carbon::parse($tglMasukRaw);
                } catch (\Exception $e) {}
                
                $losInt = 0;
                if ($tglMasukParsed) {
                    $losInt = (int)$tglMasukParsed->diffInDays(now()->startOfDay());
                }
                $eq->los_calculated = $losInt;
            }

            // Sortir Collection
            if ($sort === 'los_terlama') {
                $allEquipments = $allEquipments->sortByDesc('los_calculated');
            } else {
                $allEquipments = $allEquipments->sortBy('los_calculated');
            }

            // Paginasi Manual
            $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
            
            $equipmentsPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $allEquipments->forPage($page, $perPage),
                $allEquipments->count(),
                $perPage,
                $page,
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );
            $equipmentsPaginator->appends($request->all());
        } else {
            // Jika bukan sort LOS, pakai paginasi SQL biasa (lebih cepat)
            $equipmentsPaginator = $query->paginate($perPage)->appends($request->all());
        }
        $equipments = Equipment::all(); // untuk modal daftar pilihan dropdown
        
        $patientsMap = $this->fetchApiPatientsMap();

        // Calculate visual layout summary metrics
        $totalPasien = Equipment::count();
        $pasienBaru = Equipment::whereDate('created_at', today())
            ->orWhereDate('tanggal_pengadaan', today())
            ->count();
        $dalamPerawatan = \App\Models\Bed::where('status', 'terisi')->count();
        if ($dalamPerawatan == 0) {
            $dalamPerawatan = Equipment::whereNotNull('lokasi')->count();
        }
        $siapPulang = Equipment::where(function($q) {
            $q->whereNotNull('rencana_pulang')
              ->where('rencana_pulang', '!=', '')
              ->where('rencana_pulang', '!=', '-');
        })->count();
        $adaBarrier = Equipment::where(function($q) {
            $q->whereNotNull('alkes_invasif')
              ->where('alkes_invasif', '!=', '')
              ->where('alkes_invasif', '!=', '-');
        })->count();
        
        return view('maintenances.index', compact(
            'equipmentsPaginator', 'equipments', 'search', 'sort', 'patientsMap',
            'totalPasien', 'pasienBaru', 'dalamPerawatan', 'siapPulang', 'adaBarrier'
        ));
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
            'dpjp_utama' => 'nullable|string',
            'visit_dpjp_check' => 'nullable',
            'dokter_konsul' => 'nullable|array',
            'dokter_konsul_check' => 'nullable|array',
            'handover_pagi' => 'nullable|string',
            'handover_sore' => 'nullable|string',
            'handover_malam' => 'nullable|string',
            'planning_lab_check' => 'nullable',
            'planning_lab' => 'nullable|string',
            'planning_radiologi_check' => 'nullable',
            'planning_radiologi' => 'nullable|string',
            'planning_konsul_check' => 'nullable',
            'planning_konsul' => 'nullable|string',
            'planning_tindakan_check' => 'nullable',
            'planning_tindakan' => 'nullable|string',
            'planning_edukasi_check' => 'nullable',
            'planning_edukasi' => 'nullable|string',
            'npja' => 'nullable|string',
            'ews' => 'nullable|string',
            'tingkat_ketergantungan' => 'nullable|string',
            'ners_bertugas' => 'nullable|string',
            'alkes_invasif' => 'nullable|string',
            'tindakan_detail' => 'nullable|string',
            'type' => 'nullable|string',
            'billing_aktual' => 'nullable|string',
            'pagu_budget' => 'nullable|string',
            'kategori_pasien' => 'nullable|string',
            'target_los' => 'nullable|string',
            'notes_num' => 'nullable|string',
            'notes_case_manager' => 'nullable|string',
            'riw_lab' => 'nullable|string',
            'riw_rad' => 'nullable|string',
            'riw_obat' => 'nullable|string',
            'rencana_prosedur' => 'nullable|string',
            'rencana_diagnostik' => 'nullable|string',
            'rencana_konsul' => 'nullable|string',
            'ners_pagi' => 'nullable|string',
            'ners_siang' => 'nullable|string',
            'ners_malam' => 'nullable|string',
        ]);

        // 1. Combine Dokter Konsul per-doctor with checkbox state
        $dokterKonsulStr = '';
        if ($request->filled('dokter_konsul')) {
            $dokterKonsulArray = $request->input('dokter_konsul');
            $dokterCheckArray = $request->input('dokter_konsul_check', []);
            $combined = [];
            foreach ($dokterKonsulArray as $index => $name) {
                $name = trim($name);
                if ($name !== '') {
                    $isChecked = in_array((string)$index, $dokterCheckArray);
                    $prefix = $isChecked ? '[v] ' : '[ ] ';
                    $combined[] = $prefix . $name;
                }
            }
            $dokterKonsulStr = implode(', ', $combined);
        }

        // 2. Combine Handover shifts into spesifikasi
        $spesifikasi = '';
        $spesifikasi .= 'Pagi: ' . ($request->filled('handover_pagi') ? trim($request->input('handover_pagi')) : '-') . "\n";
        $spesifikasi .= 'Sore: ' . ($request->filled('handover_sore') ? trim($request->input('handover_sore')) : '-') . "\n";
        $spesifikasi .= 'Malam: ' . ($request->filled('handover_malam') ? trim($request->input('handover_malam')) : '-');

        // 3. Combine Planning checklist into planning_pasien
        $planning = '';
        if ($request->has('planning_lab_check')) {
            $planning .= 'Lab: ' . ($request->filled('planning_lab') ? trim($request->input('planning_lab')) : '-') . "\n";
        }
        if ($request->has('planning_radiologi_check')) {
            $planning .= 'Radiologi: ' . ($request->filled('planning_radiologi') ? trim($request->input('planning_radiologi')) : '-') . "\n";
        }
        if ($request->has('planning_konsul_check')) {
            $planning .= 'Konsul: ' . ($request->filled('planning_konsul') ? trim($request->input('planning_konsul')) : '-') . "\n";
        }
        if ($request->has('planning_tindakan_check')) {
            $planning .= 'Tindakan: ' . ($request->filled('planning_tindakan') ? trim($request->input('planning_tindakan')) : '-') . "\n";
        }
        if ($request->has('planning_edukasi_check')) {
            $planning .= 'Edukasi: ' . ($request->filled('planning_edukasi') ? trim($request->input('planning_edukasi')) : '-') . "\n";
        }
        $planning = trim($planning);

        // 4. Handle visit_dpjp value
        $visitDpjp = $request->has('visit_dpjp_check') ? 'Sudah' : 'Belum';

        // Update data array
        $data = $request->all();
        $data['dokter_konsul'] = $dokterKonsulStr;
        $data['spesifikasi'] = $spesifikasi;
        $data['planning_pasien'] = $planning;
        $data['visit_dpjp'] = $visitDpjp;

        // Clean currency amounts for case manager billing and pagu
        $billingRaw = $request->input('billing_aktual');
        $paguRaw = $request->input('pagu_budget');
        $billingClean = $billingRaw !== null && $billingRaw !== '' ? (int)preg_replace('/[^0-9]/', '', $billingRaw) : null;
        $paguClean = $paguRaw !== null && $paguRaw !== '' ? (int)preg_replace('/[^0-9]/', '', $paguRaw) : null;

        $persentasePagu = '';
        if ($billingClean !== null && $paguClean !== null && $paguClean > 0) {
            $persentasePagu = round(($billingClean / $paguClean) * 100) . '%';
        }

        $data['billing_aktual'] = $billingClean;
        $data['pagu_budget'] = $paguClean;
        $data['persentase_pagu'] = $persentasePagu;

        // Strip read-only/disabled and removed fields
        unset($data['registered_date']);
        unset($data['los_aktual']);
        unset($data['rencana_pulang']);
        unset($data['dpjp_raber']); // DPJP Raber removed

        $equipment->update($data);

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
