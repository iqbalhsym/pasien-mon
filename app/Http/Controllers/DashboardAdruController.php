<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Admission;
use App\Models\DischargeChecklist;
use App\Models\OutstandingItem;
use Inertia\Inertia;
use Illuminate\Support\Carbon;

class DashboardAdruController extends Controller
{
    private function getDashboardData()
    {
        $query = Admission::with(['checklist', 'outstandingItems'])
            ->orderBy('tgl_rencana_pulang', 'desc')
            ->orderBy('id', 'desc');

        if (request()->filled('start_date') && request()->filled('end_date')) {
            $start = request('start_date');
            $end = request('end_date') . ' 23:59:59';
            $query->where(function($q) use ($start, $end) {
                $q->whereBetween('tgl_aktual_pulang', [$start, $end])
                  ->orWhereBetween('tgl_rencana_pulang', [$start, $end])
                  ->orWhereBetween('created_at', [$start, $end]);
            });
        }

        $patients = $query->get();

        // 2. Fetch Outstanding Alkes
        $alkesList = OutstandingItem::with('admission')
            ->where('unit', 'Bedside')
            ->where('status', 'pending')
            ->get();

        // 3. Fetch Outstanding Tindakan
        $tindakanList = OutstandingItem::with('admission')
            ->where('unit', 'Tindakan')
            ->where('status', 'pending')
            ->get();

        // 4. Calculate ICU Historical Statistics
        $icuAdmissions = Admission::whereIn('floor', ['ICU', 'ICCU', 'NICU', 'PICU'])
            ->with('checklist')
            ->get();

        $icuRoomCounts = ['ICU' => 0, 'ICCU' => 0, 'NICU' => 0, 'PICU' => 0];
        $icuStatusCounts = ['Pulang' => 0, 'Meninggal' => 0, 'Rujuk' => 0, 'Pindah Jaminan' => 0];
        $icuTotalBillByInsurance = [];
        $icuProcessStatusCounts = ['DONE' => 0, 'BELUM' => 0, 'BY WA' => 0];
        $icuCotYes = 0;
        $icuTotalLos = 0;
        
        foreach ($icuAdmissions as $adm) {
            // Room Count
            if (isset($icuRoomCounts[$adm->floor])) {
                $icuRoomCounts[$adm->floor]++;
            }
            // Status Count
            $status = $adm->status_akhir;
            if ($status === 'Selesai') $status = 'Pulang'; // map Selesai to Pulang
            if (isset($icuStatusCounts[$status])) {
                $icuStatusCounts[$status]++;
            }
            // Billing by Insurance
            $ins = $adm->insurance ?? 'Umum';
            if (!isset($icuTotalBillByInsurance[$ins])) {
                $icuTotalBillByInsurance[$ins] = 0;
            }
            $icuTotalBillByInsurance[$ins] += $adm->total_bill;
            // COT Status
            if ($adm->checklist && $adm->checklist->cot_status === 'YA') {
                $icuCotYes++;
            }
            // Length of Stay
            $icuTotalLos += $adm->length_of_stay;
            // Parse process description
            $ket = strtoupper($adm->checklist->keterangan_proses ?? '');
            if (str_contains($ket, 'DONE')) {
                $icuProcessStatusCounts['DONE']++;
            } elseif (str_contains($ket, 'BELUM') || str_contains($ket, 'TUNDA') || str_contains($ket, 'KENDALA')) {
                $icuProcessStatusCounts['BELUM']++;
            } elseif (str_contains($ket, 'WA')) {
                $icuProcessStatusCounts['BY WA']++;
            } else {
                $icuProcessStatusCounts['DONE']++; // Default if empty
            }
        }

        $icuStats = [
            'total_discharge' => $icuAdmissions->count(),
            'room_counts' => $icuRoomCounts,
            'status_counts' => $icuStatusCounts,
            'avg_los' => $icuAdmissions->count() > 0 ? round($icuTotalLos / $icuAdmissions->count(), 1) : 0,
            'cot_percentage' => $icuAdmissions->count() > 0 ? round(($icuCotYes / $icuAdmissions->count()) * 100, 1) : 0,
            'billing_by_insurance' => $icuTotalBillByInsurance,
            'process_status_counts' => $icuProcessStatusCounts
        ];

        // 5. Calculate Discharge Lead Time & Wait Times
        $leadTimePatients = Admission::whereNotNull('tgl_aktual_pulang')
            ->whereNotNull('tgl_rencana_pulang')
            ->get();

        $floorsLeadTime = [];
        $delayDistribution = ['< 2 Jam' => 0, '2-6 Jam' => 0, '> 6 Jam' => 0, '> 1 Hari' => 0];

        foreach ($leadTimePatients as $adm) {
            $rencana = Carbon::parse(($adm->tgl_rencana_pulang ?? date('Y-m-d')) . ' ' . ($adm->jam_rencana_pulang ?? '08:00:00'));
            $aktual = Carbon::parse($adm->tgl_aktual_pulang ?? now());
            
            $diffInHours = $rencana->diffInHours($aktual, false);
            if ($diffInHours < 0) $diffInHours = 0; // Negative difference means ahead of schedule

            // Floor-specific lead time
            $fl = $adm->floor;
            if (!isset($floorsLeadTime[$fl])) {
                $floorsLeadTime[$fl] = ['total_hours' => 0, 'count' => 0];
            }
            $floorsLeadTime[$fl]['total_hours'] += $diffInHours;
            $floorsLeadTime[$fl]['count']++;

            // Delay distribution
            if ($diffInHours < 2) {
                $delayDistribution['< 2 Jam']++;
            } elseif ($diffInHours <= 6) {
                $delayDistribution['2-6 Jam']++;
            } elseif ($diffInHours <= 24) {
                $delayDistribution['> 6 Jam']++;
            } else {
                $delayDistribution['> 1 Hari']++;
            }
        }

        $avgLeadTimePerFloor = [];
        foreach ($floorsLeadTime as $fl => $data) {
            $avgLeadTimePerFloor[$fl] = $data['count'] > 0 ? round($data['total_hours'] / $data['count'], 1) : 0;
        }

        $leadTimeStats = [
            'avg_lead_time_per_floor' => $avgLeadTimePerFloor,
            'delay_distribution' => $delayDistribution
        ];

        // 6. Fetch COT / Cathlab / Luar OK procedures
        $cotProcedures = Admission::whereHas('checklist', function($q) {
                $q->where('cot_status', 'YA');
            })
            ->with(['checklist', 'outstandingItems'])
            ->get()
            ->map(function($adm) {
                // Find associated outstanding tindakan
                $tindakan = $adm->outstandingItems->where('unit', 'Tindakan')->first();
                $tglTindakan = $tindakan ? Carbon::parse($tindakan->tgl_tindakan) : Carbon::parse($adm->tgl_rencana_pulang);
                $tglInput = ($tindakan && $tindakan->tgl_input_sistem) ? Carbon::parse($tindakan->tgl_input_sistem) : null;
                
                $agingDays = $tglInput ? $tglTindakan->diffInDays($tglInput) : $tglTindakan->diffInDays(Carbon::today());

                return [
                    'id' => $adm->id,
                    'rm' => $adm->rm,
                    'name' => $adm->name,
                    'type' => $adm->room === 'COT' || $adm->room === 'Cathlab' ? $adm->room : 'Luar OK',
                    'tgl_tindakan' => $tglTindakan->format('Y-m-d'),
                    'tgl_input' => $tglInput ? $tglInput->format('Y-m-d') : null,
                    'aging_days' => $agingDays,
                    'status_pagu' => $adm->checklist->admin_ok_status === 'success' ? 'Sudah Dipagu' : 'Belum Dipagu',
                    'status_input' => ($tindakan && $tindakan->status === 'completed') ? 'Sudah Diinput' : 'Belum Diinput',
                    'doctor_operator' => $adm->dpjp,
                    'doctor_anestesi' => 'dr. Anestesi, Sp.An',
                    'operator_fee' => $adm->checklist->operator_fee,
                    'anestesi_fee' => $adm->checklist->anestesi_fee
                ];
            });

        $ranapCount = Admission::whereNotIn('floor', ['ICU', 'ICCU', 'NICU', 'PICU', 'COT'])->count();
        $icuCount = Admission::whereIn('floor', ['ICU', 'ICCU', 'NICU', 'PICU'])->count();
        $cotCount = DischargeChecklist::where('cot_status', 'YA')->count();
        $outstandingCount = OutstandingItem::count();

        return [
            'patients' => $patients,
            'alkesList' => $alkesList,
            'tindakanList' => $tindakanList,
            'icuStats' => $icuStats,
            'leadTimeStats' => $leadTimeStats,
            'cotProcedures' => $cotProcedures,
            'ranapCount' => $ranapCount,
            'icuCount' => $icuCount,
            'cotCount' => $cotCount,
            'outstandingCount' => $outstandingCount
        ];
    }

    public function dashboard()
    {
        return view('adru.dashboard', $this->getDashboardData());
    }

    public function billing()
    {
        $data = $this->getDashboardData();

        try {
            $response = Http::withoutVerifying()
                ->withToken('rsui_bed_mon_secret_key_2026')
                ->timeout(10)
                ->get('https://bed-monitoring.rs.ui.ac.id/api/external/discharged-patients');
            
            if ($response->successful()) {
                $apiRaw = $response->json();
                // API returns { success: true, data: [...] }
                $apiData = $apiRaw['data'] ?? $apiRaw;
                $data['apiDischargedPatients'] = collect($apiData)->map(function($p) {
                    return (object)[
                        'id'                => 'api_' . ($p['id'] ?? ''),
                        'rm'                => $p['no_rm'] ?? '-',
                        'name'              => $p['name'] ?? '-',
                        'room'              => $p['room_name'] ?? ($p['bed_number'] ?? '-'),
                        'floor'             => $p['floor'] ?? '-',
                        'insurance'         => $p['guarantor'] ?? '-',
                        'hak_kelas'         => $p['hak_kelas'] ?? null,
                        'gender'            => $p['gender'] ?? null,
                        'age'               => $p['age'] ?? null,
                        'status_akhir'      => 'Pulang',
                        'tgl_rencana_pulang'=> $p['waktu_pulang'] ?? null,
                        'tgl_aktual_pulang' => $p['waktu_pulang'] ?? null,
                        'length_of_stay'    => null,
                        'total_bill'        => 0,
                        'diagnosa'          => $p['diagnosa_medis'] ?? '-',
                        'dpjp'              => !empty($p['dpjp']) ? $p['dpjp'] : null,
                        'is_api'            => true,
                    ];
                });
            } else {
                $data['apiDischargedPatients'] = collect([]);
            }
        } catch (\Exception $e) {
            $data['apiDischargedPatients'] = collect([]);
        }

        return view('adru.billing', $data);
    }

    public function gantungan()
    {
        return view('adru.gantungan', $this->getDashboardData());
    }

    public function manual()
    {
        return view('adru.manual', $this->getDashboardData());
    }

    public function importExport()
    {
        return view('adru.import_export', $this->getDashboardData());
    }

    public function laporan()
    {
        return view('adru.laporan', $this->getDashboardData());
    }

    public function updateChecklistStep(Request $request, $id)
    {
        $checklist = DischargeChecklist::findOrFail($id);
        $field = $request->input('step_key'); // 'lab_status', 'bedside_status', etc.
        $status = $request->input('status'); // 'success', 'pending', 'error', 'none'
        
        if (in_array($field, ['lab_status', 'bedside_status', 'admin_ok_status', 'farmasi_status', 'radiologi_status'])) {
            $checklist->update([$field => $status]);
        }

        // Re-evaluate Admission status
        $admission = $checklist->admission;
        
        $steps = [
            $checklist->lab_status,
            $checklist->bedside_status,
            $checklist->admin_ok_status,
            $checklist->farmasi_status,
            $checklist->radiologi_status
        ];

        $allSuccess = true;
        $anyError = false;
        foreach ($steps as $step) {
            if ($step === 'error') $anyError = true;
            if ($step !== 'success' && $step !== 'none') $allSuccess = false;
        }

        if ($allSuccess) {
            $admission->update(['status_akhir' => 'Siap Billing']);
        } elseif ($anyError) {
            $admission->update(['status_akhir' => 'Belum Selesai']);
        } else {
            $admission->update(['status_akhir' => 'Proses Unit']);
        }

        return redirect()->back();
    }

    public function resolveOutstandingItem(Request $request, $id)
    {
        $item = OutstandingItem::findOrFail($id);
        $item->update([
            'status' => 'completed',
            'tgl_input_sistem' => Carbon::today()
        ]);

        // Auto update checklist status of the associated admission
        $admission = $item->admission;
        if ($admission && $admission->checklist) {
            $checklist = $admission->checklist;
            if ($item->unit === 'Bedside') {
                $checklist->update(['bedside_status' => 'success']);
            } elseif ($item->unit === 'Tindakan') {
                $checklist->update(['admin_ok_status' => 'success']);
            }

            // Re-evaluate overall status
            $steps = [
                $checklist->lab_status,
                $checklist->bedside_status,
                $checklist->admin_ok_status,
                $checklist->farmasi_status,
                $checklist->radiologi_status
            ];
            $allSuccess = true;
            $anyError = false;
            foreach ($steps as $step) {
                if ($step === 'error') $anyError = true;
                if ($step !== 'success' && $step !== 'none') $allSuccess = false;
            }

            if ($allSuccess) {
                $admission->update(['status_akhir' => 'Siap Billing']);
            } elseif ($anyError) {
                $admission->update(['status_akhir' => 'Belum Selesai']);
            } else {
                $admission->update(['status_akhir' => 'Proses Unit']);
            }
        }

        return redirect()->back();
    }

    public function bypassObstacle(Request $request, $id)
    {
        $admission = Admission::findOrFail($id);
        $admission->update(['status_akhir' => 'Siap Billing']);

        if ($admission->checklist) {
            $admission->checklist->update([
                'lab_status' => 'success',
                'bedside_status' => 'success',
                'admin_ok_status' => 'success',
                'farmasi_status' => 'success',
                'radiologi_status' => 'success',
            ]);
        }

        // Mark any associated outstanding items as completed
        OutstandingItem::where('admission_id', $id)->update([
            'status' => 'completed',
            'tgl_input_sistem' => Carbon::today()
        ]);

        return redirect()->back();
    }

    public function completeBilling($id)
    {
        $admission = Admission::findOrFail($id);
        $admission->update([
            'status_akhir' => 'Selesai',
            'tgl_aktual_pulang' => \Carbon\Carbon::today()
        ]);

        return redirect()->back();
    }

    public function usersIndex()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $users = \App\Models\User::orderBy('name', 'asc')->get();

        return Inertia::render('ManajemenAkun', [
            'users' => $users
        ]);
    }

    public function usersStore(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|string|in:admin,user'
        ]);

        \App\Models\User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role
        ]);

        return redirect()->back();
    }

    public function usersUpdate(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $user = \App\Models\User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|string|in:admin,user'
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        if ($user->username !== 'mohammad.hud') {
            $data['username'] = $request->username;
            $data['role'] = $request->role;
        }

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        return redirect()->back();
    }

    public function usersDestroy($id)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized.');
        }

        $user = \App\Models\User::findOrFail($id);

        if ($user->username === 'mohammad.hud') {
            abort(403, 'Cannot delete root administrator.');
        }

        $user->delete();

        return redirect()->back();
    }

    public function storeRanap(Request $request)
    {
        if (auth()->user()->role !== 'admin' && auth()->user()->role !== 'user') {
            abort(403);
        }

        $request->validate([
            'rm' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'insurance' => 'required|string|max:255',
            'room' => 'required|string|max:255',
            'floor' => 'required|string|max:255',
            'dpjp' => 'required|string|max:255',
            'tgl_rencana_pulang' => 'required|date',
            'jam_rencana_pulang' => 'nullable|string',
            'lab_status' => 'required|string|in:none,success,pending,error',
            'bedside_status' => 'required|string|in:none,success,pending,error',
            'admin_ok_status' => 'required|string|in:none,success,pending,error',
            'farmasi_status' => 'required|string|in:none,success,pending,error',
            'radiologi_status' => 'required|string|in:none,success,pending,error',
            'cot_status' => 'required|string|in:YA,TIDAK',
            'total_bill' => 'nullable|numeric',
            'keterangan_proses' => 'nullable|string',
            'notes' => 'nullable|string',
            'action' => 'nullable|string'
        ]);

        $rm = $request->rm ?: ('00-' . rand(10, 99) . '-' . rand(10, 99) . '-' . rand(10, 99));

        $statusAkhir = 'Open';
        $tglAktualPulang = null;
        
        if ($request->action === 'selesai') {
            $statusAkhir = 'Selesai';
            $tglAktualPulang = \Carbon\Carbon::now();
        }

        $admission = Admission::create([
            'rm' => $rm,
            'name' => $request->name,
            'insurance' => $request->insurance,
            'room' => $request->room,
            'floor' => $request->floor,
            'dpjp' => $request->dpjp,
            'tgl_rencana_pulang' => $request->tgl_rencana_pulang,
            'jam_rencana_pulang' => $request->jam_rencana_pulang ?? '08:00:00',
            'tgl_aktual_pulang' => $tglAktualPulang,
            'status_akhir' => $statusAkhir,
            'length_of_stay' => rand(2, 6),
            'total_bill' => $request->total_bill ?? 5000000.00,
            'notes' => $request->notes
        ]);

        DischargeChecklist::create([
            'admission_id' => $admission->id,
            'lab_status' => $request->lab_status,
            'bedside_status' => $request->bedside_status,
            'admin_ok_status' => $request->admin_ok_status,
            'farmasi_status' => $request->farmasi_status,
            'radiologi_status' => $request->radiologi_status,
            'cot_status' => $request->cot_status,
            'operator_fee' => 0,
            'anestesi_fee' => 0,
            'keterangan_proses' => $request->keterangan_proses
        ]);

        return redirect()->back();
    }

    public function storeIcu(Request $request)
    {
        if (auth()->user()->role !== 'admin' && auth()->user()->role !== 'user') {
            abort(403);
        }

        $request->validate([
            'rm' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'insurance' => 'required|string|max:255',
            'room' => 'required|string|max:255',
            'floor' => 'required|string|in:ICU,ICCU,NICU,PICU',
            'tgl_registrasi' => 'required|date',
            'tgl_pulang' => 'required|date',
            'tgl_discharge' => 'required|date',
            'status' => 'required|string|in:Pulang,Meninggal,Rujuk,Pindah Jaminan',
            'total_bill' => 'required|numeric',
            'cot_status' => 'required|string|in:YA,TIDAK',
            'keterangan_proses' => 'nullable|string'
        ]);

        $los = max(1, Carbon::parse($request->tgl_registrasi)->diffInDays(Carbon::parse($request->tgl_pulang)));

        $admission = Admission::create([
            'rm' => $request->rm,
            'name' => $request->name,
            'insurance' => $request->insurance,
            'room' => $request->room,
            'floor' => $request->floor,
            'dpjp' => 'dr. Andi S, Sp.PD',
            'tgl_rencana_pulang' => $request->tgl_discharge,
            'jam_rencana_pulang' => '08:00:00',
            'tgl_aktual_pulang' => $request->tgl_pulang,
            'status_akhir' => $request->status === 'Pulang' ? 'Selesai' : $request->status,
            'length_of_stay' => $los,
            'total_bill' => $request->total_bill,
            'notes' => $request->keterangan_proses
        ]);

        DischargeChecklist::create([
            'admission_id' => $admission->id,
            'lab_status' => 'success',
            'bedside_status' => 'success',
            'admin_ok_status' => 'success',
            'farmasi_status' => 'success',
            'radiologi_status' => 'success',
            'cot_status' => $request->cot_status,
            'operator_fee' => $request->cot_status === 'YA' ? rand(1500000, 5000000) : 0,
            'anestesi_fee' => $request->cot_status === 'YA' ? rand(500000, 2000000) : 0,
            'keterangan_proses' => $request->keterangan_proses ?? 'DONE'
        ]);

        return redirect()->back();
    }

    public function storeCot(Request $request)
    {
        if (auth()->user()->role !== 'admin' && auth()->user()->role !== 'user') {
            abort(403);
        }

        $request->validate([
            'rm' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'doctor_operator' => 'required|string|max:255',
            'operator_fee' => 'required|numeric',
            'doctor_anestesi' => 'nullable|string|max:255',
            'anestesi_fee' => 'nullable|numeric'
        ]);

        $admission = Admission::where('rm', $request->rm)->first();
        if (!$admission) {
            $admission = Admission::create([
                'rm' => $request->rm,
                'name' => $request->name,
                'insurance' => 'BPJS Kesehatan',
                'room' => 'COT Bed ' . rand(1, 4),
                'floor' => 'COT',
                'dpjp' => $request->doctor_operator,
                'tgl_rencana_pulang' => Carbon::today()->toDateString(),
                'jam_rencana_pulang' => '12:00:00',
                'tgl_aktual_pulang' => null,
                'status_akhir' => 'Open',
                'length_of_stay' => 1,
                'total_bill' => $request->operator_fee + ($request->anestesi_fee ?? 0) + 3000000
            ]);
        }

        $checklist = DischargeChecklist::where('admission_id', $admission->id)->first();
        if (!$checklist) {
            $checklist = DischargeChecklist::create([
                'admission_id' => $admission->id,
                'lab_status' => 'success',
                'bedside_status' => 'success',
                'admin_ok_status' => 'pending',
                'farmasi_status' => 'success',
                'radiologi_status' => 'success',
                'cot_status' => 'YA',
                'operator_fee' => $request->operator_fee,
                'anestesi_fee' => $request->anestesi_fee ?? 0,
                'keterangan_proses' => $request->doctor_anestesi ?? 'dr. Komang Ayu Ferdiana, Sp.An'
            ]);
        } else {
            $checklist->update([
                'cot_status' => 'YA',
                'operator_fee' => $request->operator_fee,
                'anestesi_fee' => $request->anestesi_fee ?? 0,
                'keterangan_proses' => $request->doctor_anestesi ?? 'dr. Komang Ayu Ferdiana, Sp.An'
            ]);
        }

        OutstandingItem::create([
            'admission_id' => $admission->id,
            'unit' => 'Tindakan',
            'category' => 'Tindakan COT',
            'item_name' => 'Tindakan Operatif COT oleh ' . $request->doctor_operator,
            'price' => $request->operator_fee + ($request->anestesi_fee ?? 0),
            'tgl_tindakan' => Carbon::today(),
            'tgl_input_sistem' => null,
            'status' => 'pending'
        ]);

        return redirect()->back();
    }

    public function storeOutstanding(Request $request)
    {
        if (auth()->user()->role !== 'admin' && auth()->user()->role !== 'user') {
            abort(403);
        }

        $request->validate([
            'admission_id' => 'required|exists:admissions,id',
            'unit' => 'required|string|in:Farmasi,Laboratorium,Radiologi,Bedside,Admin OK,Tindakan',
            'category' => 'required|string|max:255',
            'item_name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'tgl_tindakan' => 'required|date'
        ]);

        OutstandingItem::create([
            'admission_id' => $request->admission_id,
            'unit' => $request->unit,
            'category' => $request->category,
            'item_name' => $request->item_name,
            'price' => $request->price,
            'tgl_tindakan' => $request->tgl_tindakan,
            'tgl_input_sistem' => null,
            'status' => 'pending'
        ]);

        $admission = Admission::find($request->admission_id);
        if ($admission && $admission->checklist) {
            $checklist = $admission->checklist;
            if ($request->unit === 'Bedside') {
                $checklist->update(['bedside_status' => 'error']);
            } elseif ($request->unit === 'Tindakan' || $request->unit === 'Admin OK') {
                $checklist->update(['admin_ok_status' => 'error']);
            } elseif ($request->unit === 'Laboratorium') {
                $checklist->update(['lab_status' => 'error']);
            } elseif ($request->unit === 'Farmasi') {
                $checklist->update(['farmasi_status' => 'error']);
            } elseif ($request->unit === 'Radiologi') {
                $checklist->update(['radiologi_status' => 'error']);
            }
            $admission->update(['status_akhir' => 'Belum Selesai']);
        }

        return redirect()->back();
    }

    public function downloadTemplate($type)
    {
        $headers = [];
        $filename = "template_{$type}.csv";

        switch ($type) {
            case 'ranap':
                $headers = [
                    'nama_pasien', 'jaminan_cara_bayar', 'ruangan', 'lantai_floor', 'dokter_dpjp', 
                    'rencana_tgl_pulang_yyyy_mm_dd', 'rencana_jam_pulang_hh_mm_ss', 
                    'status_lab_none_success_pending_error', 'status_bedside_none_success_pending_error', 
                    'status_admin_ok_none_success_pending_error', 'status_farmasi_none_success_pending_error', 
                    'status_radiologi_none_success_pending_error', 'status_tindakan_cot_ya_tidak', 
                    'tarif_operator_fee', 'tarif_anestesi_fee', 'keterangan_proses', 'catatan_notes'
                ];
                break;
            case 'icu':
                $headers = [
                    'nrm', 'nama_pasien', 'jaminan_cara_bayar', 'ruangan', 'jenis_floor_icu_iccu_nicu_picu', 
                    'tgl_registrasi_yyyy_mm_dd', 'tgl_pulang_yyyy_mm_dd', 'tgl_discharge_yyyy_mm_dd', 
                    'status_kepulangan_pulang_meninggal_rujuk_pindah', 'total_bill_nominal', 
                    'status_tindakan_cot_ya_tidak', 'keterangan_proses'
                ];
                break;
            case 'cot':
                $headers = [
                    'nrm', 'nama_pasien', 'dokter_operator', 'tarif_operator_fee', 
                    'dokter_anestesi', 'tarif_anestesi_fee'
                ];
                break;
            case 'outstanding':
                $headers = [
                    'nrm_pasien', 'unit_penunjang_farmasi_lab_rad_bedside_admin_tindakan', 
                    'kategori_gantungan', 'nama_barang_tindakan', 'tarif_nominal', 'tgl_tindakan_yyyy_mm_dd'
                ];
                break;
            default:
                abort(404);
        }

        $callback = function() use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ]);
    }

    public function exportData($type)
    {
        $headers = [];
        $data = [];
        $filename = "export_{$type}_" . date('Ymd_His') . ".csv";

        switch ($type) {
            case 'ranap':
                $headers = [
                    'Nama Pasien', 'Jaminan / Cara Bayar', 'Ruangan', 'Lantai / Floor', 'Dokter DPJP', 
                    'Rencana Tgl Pulang', 'Rencana Jam Pulang', 'Status Lab', 'Status Bedside', 
                    'Status Admin OK', 'Status Farmasi', 'Status Radiologi', 'Tindakan COT', 
                    'Tarif Operator Fee', 'Tarif Anestesi Fee', 'Keterangan Proses', 'Catatan / Notes'
                ];
                $admissions = Admission::whereNotIn('floor', ['ICU', 'ICCU', 'NICU', 'PICU', 'COT'])
                    ->with('checklist')->get();
                foreach ($admissions as $adm) {
                    $data[] = [
                        $adm->name, $adm->insurance, $adm->room, $adm->floor, $adm->dpjp,
                        $adm->tgl_rencana_pulang, $adm->jam_rencana_pulang,
                        $adm->checklist?->lab_status ?? 'none', $adm->checklist?->bedside_status ?? 'none',
                        $adm->checklist?->admin_ok_status ?? 'none', $adm->checklist?->farmasi_status ?? 'none',
                        $adm->checklist?->radiologi_status ?? 'none', $adm->checklist?->cot_status ?? 'TIDAK',
                        $adm->checklist?->operator_fee ?? 0, $adm->checklist?->anestesi_fee ?? 0,
                        $adm->checklist?->keterangan_proses ?? '', $adm->notes ?? ''
                    ];
                }
                break;
            case 'icu':
                $headers = [
                    'NRM', 'Nama Pasien', 'Jaminan / Cara Bayar', 'Ruangan', 'Asal Ruangan (ICU/NICU)', 
                    'Tgl Registrasi', 'Tgl Pulang', 'Tgl Discharge', 'Status Kepulangan', 'Total Billing', 
                    'Tindakan COT', 'Keterangan Proses'
                ];
                $admissions = Admission::whereIn('floor', ['ICU', 'ICCU', 'NICU', 'PICU'])
                    ->with('checklist')->get();
                foreach ($admissions as $adm) {
                    $data[] = [
                        $adm->rm, $adm->name, $adm->insurance, $adm->room, $adm->floor,
                        $adm->tgl_rencana_pulang, $adm->tgl_aktual_pulang, $adm->tgl_rencana_pulang,
                        $adm->status_akhir === 'Selesai' ? 'Pulang' : $adm->status_akhir,
                        $adm->total_bill, $adm->checklist?->cot_status ?? 'TIDAK',
                        $adm->checklist?->keterangan_proses ?? ''
                    ];
                }
                break;
            case 'cot':
                $headers = [
                    'NRM', 'Nama Pasien', 'Dokter Operator', 'Tarif Operator Fee', 
                    'Dokter Anestesi', 'Tarif Anestesi Fee'
                ];
                $checklists = DischargeChecklist::where('cot_status', 'YA')
                    ->with('admission')->get();
                foreach ($checklists as $ch) {
                    $data[] = [
                        $ch->admission?->rm ?? '', $ch->admission?->name ?? '', $ch->admission?->dpjp ?? '',
                        $ch->operator_fee, $ch->keterangan_proses ?? '', $ch->anestesi_fee
                    ];
                }
                break;
            case 'outstanding':
                $headers = [
                    'NRM Pasien', 'Unit Penunjang', 'Kategori Gantungan', 'Nama Barang / Tindakan', 
                    'Tarif Nominal', 'Tgl Tindakan'
                ];
                $items = OutstandingItem::with('admission')->get();
                foreach ($items as $item) {
                    $data[] = [
                        $item->admission?->rm ?? $item->rm ?? '', $item->unit, $item->category,
                        $item->item_name, $item->price, $item->tgl_tindakan
                    ];
                }
                break;
            case 'pasien-pulang':
                $headers = [
                    'NRM', 'Nama Pasien', 'Jaminan / Cara Bayar', 'Ruangan', 'Lantai / Floor', 
                    'Dokter DPJP', 'Tanggal Pulang', 'Jam Pulang', 'Status Akhir'
                ];
                $query = Admission::whereIn('status_akhir', ['Selesai', 'Pulang']);
                
                if (request('start_date')) {
                    $query->whereDate('tgl_rencana_pulang', '>=', request('start_date'));
                }
                if (request('end_date')) {
                    $query->whereDate('tgl_rencana_pulang', '<=', request('end_date'));
                }
                
                $admissions = $query->with('checklist')->get();
                foreach ($admissions as $adm) {
                    $data[] = [
                        $adm->rm, $adm->name, $adm->insurance, $adm->room, $adm->floor,
                        $adm->dpjp, $adm->tgl_rencana_pulang, $adm->jam_rencana_pulang,
                        $adm->status_akhir
                    ];
                }
                break;
            default:
                abort(404);
        }

        $callback = function() use ($headers, $data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            foreach ($data as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename={$filename}",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ]);
    }

    public function importData(Request $request)
    {
        if (auth()->user()->role !== 'admin' && auth()->user()->role !== 'user') {
            abort(403);
        }

        $request->validate([
            'file' => 'required|file',
            'type' => 'required|string|in:ranap,icu,cot,outstanding'
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $type = $request->type;

        $rows = $this->parseUploadedFile($file, $extension);
        if (empty($rows)) {
            return redirect()->back()->with('error', 'Gagal membaca file atau file kosong.');
        }

        $insertedCount = 0;

        foreach ($rows as $row) {
            if ($type === 'ranap') {
                $name = $row['nama_pasien'] ?? $row[0] ?? null;
                if (!$name) continue;

                $insurance = $row['jaminan_cara_bayar'] ?? $row[1] ?? 'BPJS Kesehatan';
                $room = $row['ruangan'] ?? $row[2] ?? 'Melati 1';
                $floor = $row['lantai_floor'] ?? $row[3] ?? 'Lantai 5';
                $dpjp = $row['dokter_dpjp'] ?? $row[4] ?? 'dr. Andi S, Sp.PD';
                $tgl_rencana_pulang = $row['rencana_tgl_pulang_yyyy_mm_dd'] ?? $row[5] ?? Carbon::today()->toDateString();
                
                if (is_numeric($tgl_rencana_pulang)) {
                    $tgl_rencana_pulang = Carbon::createFromTimestamp(($tgl_rencana_pulang - 25569) * 86400)->toDateString();
                }
                if (!strtotime($tgl_rencana_pulang)) {
                    $tgl_rencana_pulang = Carbon::today()->toDateString();
                }

                $jam_rencana_pulang = $row['rencana_jam_pulang_hh_mm_ss'] ?? $row[6] ?? '08:00:00';
                if (!strtotime($jam_rencana_pulang)) {
                    $jam_rencana_pulang = '08:00:00';
                }
                $lab = $row['status_lab_none_success_pending_error'] ?? $row[7] ?? 'none';
                $bedside = $row['status_bedside_none_success_pending_error'] ?? $row[8] ?? 'none';
                $admin = $row['status_admin_ok_none_success_pending_error'] ?? $row[9] ?? 'none';
                $farmasi = $row['status_farmasi_none_success_pending_error'] ?? $row[10] ?? 'none';
                $radiologi = $row['status_radiologi_none_success_pending_error'] ?? $row[11] ?? 'none';
                $cot = $row['status_tindakan_cot_ya_tidak'] ?? $row[12] ?? 'TIDAK';
                $op_fee = floatval($row['tarif_operator_fee'] ?? $row[13] ?? 0);
                $an_fee = floatval($row['tarif_anestesi_fee'] ?? $row[14] ?? 0);
                $keterangan = $row['keterangan_proses'] ?? $row[15] ?? '';
                $notes = $row['catatan_notes'] ?? $row[16] ?? '';

                $rm = '00-' . rand(10, 99) . '-' . rand(10, 99) . '-' . rand(10, 99);
                $admission = Admission::create([
                    'rm' => $rm,
                    'name' => $name,
                    'insurance' => $insurance,
                    'room' => $room,
                    'floor' => $floor,
                    'dpjp' => $dpjp,
                    'tgl_rencana_pulang' => $tgl_rencana_pulang,
                    'jam_rencana_pulang' => $jam_rencana_pulang,
                    'status_akhir' => 'Open',
                    'length_of_stay' => rand(2, 6),
                    'total_bill' => 5000000.00,
                    'notes' => $notes
                ]);

                DischargeChecklist::create([
                    'admission_id' => $admission->id,
                    'lab_status' => $lab,
                    'bedside_status' => $bedside,
                    'admin_ok_status' => $admin,
                    'farmasi_status' => $farmasi,
                    'radiologi_status' => $radiologi,
                    'cot_status' => $cot,
                    'operator_fee' => $op_fee,
                    'anestesi_fee' => $an_fee,
                    'keterangan_proses' => $keterangan
                ]);
                $insertedCount++;
            }
            else if ($type === 'icu') {
                $rm = $row['nrm'] ?? $row[0] ?? null;
                if (!$rm) continue;

                $name = $row['nama_pasien'] ?? $row[1] ?? 'Pasien ICU';
                $insurance = $row['jaminan_cara_bayar'] ?? $row[2] ?? 'BPJS';
                $room = $row['ruangan'] ?? $row[3] ?? 'ICU Bed 1';
                $floor = $row['jenis_floor_icu_iccu_nicu_picu'] ?? $row[4] ?? 'ICU';
                $tgl_reg = $row['tgl_registrasi_yyyy_mm_dd'] ?? $row[5] ?? Carbon::today()->toDateString();
                $tgl_pulang = $row['tgl_pulang_yyyy_mm_dd'] ?? $row[6] ?? Carbon::today()->toDateString();
                $tgl_discharge = $row['tgl_discharge_yyyy_mm_dd'] ?? $row[7] ?? Carbon::today()->toDateString();
                $status = $row['status_kepulangan_pulang_meninggal_rujuk_pindah'] ?? $row[8] ?? 'Pulang';
                $total_bill = floatval($row['total_bill_nominal'] ?? $row[9] ?? 10000000);
                $cot = $row['status_tindakan_cot_ya_tidak'] ?? $row[10] ?? 'TIDAK';
                $keterangan = $row['keterangan_proses'] ?? $row[11] ?? '';

                if (is_numeric($tgl_reg)) $tgl_reg = Carbon::createFromTimestamp(($tgl_reg - 25569) * 86400)->toDateString();
                if (is_numeric($tgl_pulang)) $tgl_pulang = Carbon::createFromTimestamp(($tgl_pulang - 25569) * 86400)->toDateString();
                if (is_numeric($tgl_discharge)) $tgl_discharge = Carbon::createFromTimestamp(($tgl_discharge - 25569) * 86400)->toDateString();

                if (!strtotime($tgl_reg)) $tgl_reg = Carbon::today()->toDateString();
                if (!strtotime($tgl_pulang)) $tgl_pulang = Carbon::today()->toDateString();
                if (!strtotime($tgl_discharge)) $tgl_discharge = Carbon::today()->toDateString();

                $los = max(1, Carbon::parse($tgl_reg)->diffInDays(Carbon::parse($tgl_pulang)));

                $admission = Admission::create([
                    'rm' => $rm,
                    'name' => $name,
                    'insurance' => $insurance,
                    'room' => $room,
                    'floor' => $floor,
                    'dpjp' => 'dr. Andi S, Sp.PD',
                    'tgl_rencana_pulang' => $tgl_discharge,
                    'jam_rencana_pulang' => '08:00:00',
                    'tgl_aktual_pulang' => $tgl_pulang,
                    'status_akhir' => $status === 'Pulang' ? 'Selesai' : $status,
                    'length_of_stay' => $los,
                    'total_bill' => $total_bill,
                    'notes' => $keterangan
                ]);

                DischargeChecklist::create([
                    'admission_id' => $admission->id,
                    'lab_status' => 'success',
                    'bedside_status' => 'success',
                    'admin_ok_status' => 'success',
                    'farmasi_status' => 'success',
                    'radiologi_status' => 'success',
                    'cot_status' => $cot,
                    'operator_fee' => $cot === 'YA' ? 2500000 : 0,
                    'anestesi_fee' => $cot === 'YA' ? 750000 : 0,
                    'keterangan_proses' => $keterangan
                ]);
                $insertedCount++;
            }
            else if ($type === 'cot') {
                $rm = $row['nrm'] ?? $row[0] ?? null;
                if (!$rm) continue;

                $name = $row['nama_pasien'] ?? $row[1] ?? 'Pasien COT';
                $operator = $row['dokter_operator'] ?? $row[2] ?? 'dr. Andi S, Sp.PD';
                $op_fee = floatval($row['tarif_operator_fee'] ?? $row[3] ?? 0);
                $anestesi = $row['dokter_anestesi'] ?? $row[4] ?? '';
                $an_fee = floatval($row['tarif_anestesi_fee'] ?? $row[5] ?? 0);

                $admission = Admission::where('rm', $rm)->first();
                if (!$admission) {
                    $admission = Admission::create([
                        'rm' => $rm,
                        'name' => $name,
                        'insurance' => 'BPJS Kesehatan',
                        'room' => 'COT Bed ' . rand(1, 4),
                        'floor' => 'COT',
                        'dpjp' => $operator,
                        'tgl_rencana_pulang' => Carbon::today()->toDateString(),
                        'jam_rencana_pulang' => '12:00:00',
                        'status_akhir' => 'Open',
                        'length_of_stay' => 1,
                        'total_bill' => $op_fee + $an_fee + 3000000
                    ]);
                }

                $checklist = DischargeChecklist::where('admission_id', $admission->id)->first();
                if (!$checklist) {
                    DischargeChecklist::create([
                        'admission_id' => $admission->id,
                        'lab_status' => 'success',
                        'bedside_status' => 'success',
                        'admin_ok_status' => 'pending',
                        'farmasi_status' => 'success',
                        'radiologi_status' => 'success',
                        'cot_status' => 'YA',
                        'operator_fee' => $op_fee,
                        'anestesi_fee' => $an_fee,
                        'keterangan_proses' => $anestesi
                    ]);
                } else {
                    $checklist->update([
                        'cot_status' => 'YA',
                        'operator_fee' => $op_fee,
                        'anestesi_fee' => $an_fee,
                        'keterangan_proses' => $anestesi
                    ]);
                }

                OutstandingItem::create([
                    'admission_id' => $admission->id,
                    'unit' => 'Tindakan',
                    'category' => 'Tindakan COT',
                    'item_name' => 'Tindakan Operatif COT oleh ' . $operator,
                    'price' => $op_fee + $an_fee,
                    'tgl_tindakan' => Carbon::today(),
                    'status' => 'pending'
                ]);
                $insertedCount++;
            }
            else if ($type === 'outstanding') {
                $rm = $row['nrm_pasien'] ?? $row[0] ?? null;
                if (!$rm) continue;

                $unit = $row['unit_penunjang_farmasi_lab_rad_bedside_admin_tindakan'] ?? $row[1] ?? 'Farmasi';
                $category = $row['kategori_gantungan'] ?? $row[2] ?? 'Alat Kesehatan';
                $item_name = $row['nama_barang_tindakan'] ?? $row[3] ?? 'Alkes';
                $price = floatval($row['tarif_nominal'] ?? $row[4] ?? 0);
                $tgl_tindakan = $row['tgl_tindakan_yyyy_mm_dd'] ?? $row[5] ?? Carbon::today()->toDateString();

                if (is_numeric($tgl_tindakan)) $tgl_tindakan = Carbon::createFromTimestamp(($tgl_tindakan - 25569) * 86400)->toDateString();
                if (!strtotime($tgl_tindakan)) $tgl_tindakan = Carbon::today()->toDateString();

                $admission = Admission::where('rm', $rm)->first();
                if (!$admission) continue;

                OutstandingItem::create([
                    'admission_id' => $admission->id,
                    'unit' => $unit,
                    'category' => $category,
                    'item_name' => $item_name,
                    'price' => $price,
                    'tgl_tindakan' => $tgl_tindakan,
                    'status' => 'pending'
                ]);

                if ($admission->checklist) {
                    $checklist = $admission->checklist;
                    if ($unit === 'Bedside') $checklist->update(['bedside_status' => 'error']);
                    elseif ($unit === 'Tindakan' || $unit === 'Admin OK') $checklist->update(['admin_ok_status' => 'error']);
                    elseif ($unit === 'Laboratorium') $checklist->update(['lab_status' => 'error']);
                    elseif ($unit === 'Farmasi') $checklist->update(['farmasi_status' => 'error']);
                    elseif ($unit === 'Radiologi') $checklist->update(['radiologi_status' => 'error']);
                    $admission->update(['status_akhir' => 'Belum Selesai']);
                }
                $insertedCount++;
            }
        }

        return redirect()->back()->with('success', "Berhasil mengimpor {$insertedCount} data.");
    }

    private function parseUploadedFile($file, $extension)
    {
        $rows = [];
        if (strcasecmp($extension, 'csv') === 0) {
            if (($handle = fopen($file->getRealPath(), "r")) !== FALSE) {
                $header = fgetcsv($handle, 1000, ",");
                if (!$header) return [];
                
                if (substr($header[0], 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
                    $header[0] = substr($header[0], 3);
                }

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $rowData = [];
                    foreach ($header as $key => $colName) {
                        $rowData[$colName] = $data[$key] ?? '';
                    }
                    foreach ($data as $key => $val) {
                        $rowData[$key] = $val;
                    }
                    $rows[] = $rowData;
                }
                fclose($handle);
            }
        } else if (strcasecmp($extension, 'xlsx') === 0) {
            $zip = new \ZipArchive();
            if ($zip->open($file->getRealPath()) === TRUE) {
                $sharedStrings = [];
                $sharedStringsData = $zip->getFromName('xl/sharedStrings.xml');
                if ($sharedStringsData) {
                    $xml = simplexml_load_string($sharedStringsData);
                    foreach ($xml->si as $si) {
                        $sharedStrings[] = (string)$si->t;
                    }
                }

                $sheetData = $zip->getFromName('xl/worksheets/sheet1.xml');
                if ($sheetData) {
                    $xml = simplexml_load_string($sheetData);
                    $count = 0;
                    $headerCols = [];
                    
                    foreach ($xml->sheetData->row as $row) {
                        $rowData = [];
                        $idx = 0;
                        foreach ($row->c as $c) {
                            $r = (string)$c['r'];
                            $colLetter = preg_replace('/[0-9]/', '', $r);
                            $type = (string)$c['t'];
                            $val = (string)$c->v;
                            
                            if ($type === 's') {
                                $val = $sharedStrings[(int)$val] ?? '';
                            }
                            
                            if ($count === 0) {
                                $headerCols[$colLetter] = $val;
                            } else {
                                $colHeaderName = $headerCols[$colLetter] ?? $idx;
                                $rowData[$colHeaderName] = $val;
                                $rowData[$idx] = $val;
                            }
                            $idx++;
                        }
                        if ($count > 0) {
                            $rows[] = $rowData;
                        }
                        $count++;
                    }
                }
                $zip->close();
            }
        }
        return $rows;
    }
}
