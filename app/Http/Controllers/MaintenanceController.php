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
        Equipment::resetDailyVisits();
        $search = $request->input('search');

        $sort = $request->input('sort', 'ruangan');

        $query = Equipment::withCount('maintenances')
            ->with(['media', 'bed.room', 'maintenances' => function($q) {
                $q->latest('tanggal_pelaksanaan');
            }]);

        if ($sort === 'alphabetical') {
            $query->orderBy('merk', 'asc');
        } elseif ($sort === 'alphabetical_desc') {
            $query->orderBy('merk', 'desc');
        } elseif ($sort === 'ruangan') {
            $query->leftJoin('beds', 'equipments.id', '=', 'beds.equipment_id')
                ->select('equipments.*')
                ->orderByRaw('CASE WHEN beds.id IS NULL THEN 1 ELSE 0 END')
                ->orderBy('equipments.lokasi', 'asc')
                ->orderBy('beds.bed_number', 'asc');
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
        Equipment::resetDailyVisits();
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
            'visit_dpjp' => 'nullable|string',
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
            'diagnosis_lokal' => 'nullable|string',
            'rencana_pulang' => 'nullable|string',
        ]);

        $data = [];

        // 1. DPJP Utama & visit_dpjp with visit_history log
        if ($request->has('dpjp_utama')) {
            $data['dpjp_utama'] = $request->input('dpjp_utama');
        }

        if ($request->has('visit_dpjp_check') || $request->has('visit_dpjp')) {
            $visitDpjp = $request->has('visit_dpjp_check') ? 'Sudah' : $request->input('visit_dpjp', 'Belum');
            
            $history = [];
            if ($equipment->visit_history) {
                $history = json_decode($equipment->visit_history, true) ?: [];
            }
            $todayStr = now()->toDateString();
            if ($visitDpjp === 'Sudah') {
                $hasToday = false;
                foreach ($history as $timestamp) {
                    if (date('Y-m-d', strtotime($timestamp)) === $todayStr) {
                        $hasToday = true;
                        break;
                    }
                }
                if (!$hasToday) {
                    $history[] = now()->toDateTimeString();
                }
            } else {
                $history = array_filter($history, function($timestamp) use ($todayStr) {
                    return date('Y-m-d', strtotime($timestamp)) !== $todayStr;
                });
                $history = array_values($history);
            }
            $data['visit_history'] = json_encode($history);
            $data['visit_dpjp'] = $visitDpjp;
        }

        // 2. Dokter Konsul combine & history log
        if ($request->has('dokter_konsul')) {
            $dokterKonsulArray = $request->input('dokter_konsul');
            $dokterCheckArray = $request->input('dokter_konsul_check', []);
            $combined = [];
            
            $currentHistory = [];
            if ($equipment->konsul_history) {
                $currentHistory = json_decode($equipment->konsul_history, true) ?: [];
            }
            $todayStr = now()->toDateString();

            foreach ($dokterKonsulArray as $index => $name) {
                $name = trim($name);
                if ($name !== '') {
                    $isChecked = in_array((string)$index, $dokterCheckArray);
                    
                    if (!isset($currentHistory[$name])) {
                        $currentHistory[$name] = [];
                    }

                    if ($isChecked) {
                        $hasToday = false;
                        foreach ($currentHistory[$name] as $timestamp) {
                            if (date('Y-m-d', strtotime($timestamp)) === $todayStr) {
                                $hasToday = true;
                                break;
                            }
                        }
                        if (!$hasToday) {
                            $currentHistory[$name][] = now()->toDateTimeString();
                        }
                    } else {
                        $currentHistory[$name] = array_filter($currentHistory[$name], function($timestamp) use ($todayStr) {
                            return date('Y-m-d', strtotime($timestamp)) !== $todayStr;
                        });
                        $currentHistory[$name] = array_values($currentHistory[$name]);
                    }

                    $prefix = $isChecked ? '[v] ' : '[ ] ';
                    $combined[] = $prefix . $name;
                }
            }
            $data['dokter_konsul'] = implode(', ', $combined);
            $data['konsul_history'] = json_encode($currentHistory);
        }

        // 3. Handover spesifikasi
        if ($request->has('handover_pagi') || $request->has('handover_sore') || $request->has('handover_malam')) {
            $spesifikasi = '';
            $spesifikasi .= 'Pagi: ' . ($request->filled('handover_pagi') ? trim($request->input('handover_pagi')) : '-') . "\n";
            $spesifikasi .= 'Sore: ' . ($request->filled('handover_sore') ? trim($request->input('handover_sore')) : '-') . "\n";
            $spesifikasi .= 'Malam: ' . ($request->filled('handover_malam') ? trim($request->input('handover_malam')) : '-');
            $data['spesifikasi'] = $spesifikasi;
        }

        // 4. Planning Selama Perawatan
        if ($request->has('planning_lab_check') || $request->has('planning_radiologi_check') || $request->has('planning_konsul_check') || $request->has('planning_tindakan_check') || $request->has('planning_edukasi_check') || $request->has('planning_pasien') || $request->has('planning_lain_lain')) {
            if ($request->has('planning_pasien') && !$request->has('planning_lab_check') && !$request->has('planning_radiologi_check')) {
                $data['planning_pasien'] = $request->input('planning_pasien');
            } else {
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
                if ($request->has('planning_lain_lain')) {
                    $planning .= 'Lain-lain: ' . ($request->filled('planning_lain_lain') ? trim($request->input('planning_lain_lain')) : '-') . "\n";
                }
                $data['planning_pasien'] = trim($planning);
            }
        }

        // Direct database fields
        foreach (['npja', 'ews', 'tingkat_ketergantungan', 'ners_bertugas', 'alkes_invasif', 'tindakan_detail', 'type', 'kategori_pasien', 'target_los', 'notes_num', 'notes_case_manager', 'riw_lab', 'riw_rad', 'riw_obat', 'rencana_prosedur', 'rencana_diagnostik', 'rencana_konsul', 'ners_pagi', 'ners_siang', 'ners_malam', 'diagnosis_lokal', 'rencana_pulang'] as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->input($field);
            }
        }

        // Currency calculations
        if ($request->has('billing_aktual')) {
            $billingRaw = $request->input('billing_aktual');
            $data['billing_aktual'] = $billingRaw !== null && $billingRaw !== '' ? (int)preg_replace('/[^0-9]/', '', $billingRaw) : null;
        }
        if ($request->has('pagu_budget')) {
            $paguRaw = $request->input('pagu_budget');
            $data['pagu_budget'] = $paguRaw !== null && $paguRaw !== '' ? (int)preg_replace('/[^0-9]/', '', $paguRaw) : null;
        }

        if (array_key_exists('billing_aktual', $data) || array_key_exists('pagu_budget', $data)) {
            $billingClean = array_key_exists('billing_aktual', $data) ? $data['billing_aktual'] : $equipment->billing_aktual;
            $paguClean = array_key_exists('pagu_budget', $data) ? $data['pagu_budget'] : $equipment->pagu_budget;
            $persentasePagu = '';
            if ($billingClean !== null && $paguClean !== null && $paguClean > 0) {
                $persentasePagu = round(($billingClean / $paguClean) * 100) . '%';
            }
            $data['persentase_pagu'] = $persentasePagu;
        }

        $equipment->update($data);

        return redirect()->back()
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
