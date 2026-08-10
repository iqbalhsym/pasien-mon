@extends('layouts.staradmin')
@section('title', 'Dashboard Adru')

@section('content_header')
<div class="d-sm-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
  <div>
    <h1 class="h2 text-dark font-weight-bold">Dashboard</h1>
    <p class="text-muted mb-0">Monitoring & Administrasi Kepulangan Pasien Rawat Inap.</p>
  </div>
  <div>
    <a href="{{ route('adru.export', 'ranap') }}" class="btn btn-success text-white"><i class="mdi mdi-microsoft-excel me-1"></i> Export CSV</a>
  </div>
</div>
@stop

@section('content')
@php
$billingSelesai = collect($patients)->filter(fn($p) => in_array($p->status_akhir, ['Siap Billing', 'Selesai']))->count();
$menungguBilling = collect($patients)->filter(fn($p) => in_array($p->status_akhir, ['Proses Unit', 'Open']))->count();
$transaksiGantungan = collect($patients)->filter(fn($p) => in_array($p->status_akhir, ['Belum Selesai', 'Tunda Pulang']))->count();
$totalPasien = count($patients);
$uniqueFloors = $patients->pluck('floor')->filter()->unique()->sort()->values();
$uniqueInsurances = $patients->pluck('insurance')->filter()->unique()->sort()->values();
$uniqueStatuses = $patients->pluck('status_akhir')->filter()->unique()->sort()->values();
@endphp

{{-- Flash success message --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="mdi mdi-check-circle me-2"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- KPI SUMMARY CARDS --}}
<div class="row mb-4">
    <div class="col-lg col-md-4 col-sm-6 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted text-uppercase fw-bold mb-1" style="font-size:0.72rem;">Billing Selesai</p>
                        <h3 class="fw-black text-dark mb-0">{{ $billingSelesai }}</h3>
                    </div>
                    <div class="badge badge-opacity-success p-2"><i class="mdi mdi-check-circle text-success fs-5"></i></div>
                </div>
                <p class="text-muted mt-2 mb-0" style="font-size:0.72rem;">{{ $totalPasien > 0 ? round(($billingSelesai/$totalPasien)*100, 1) : 0 }}% dari total pasien</p>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-sm-6 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted text-uppercase fw-bold mb-1" style="font-size:0.72rem;">Menunggu Billing</p>
                        <h3 class="fw-black text-dark mb-0">{{ $menungguBilling }}</h3>
                    </div>
                    <div class="badge badge-opacity-warning p-2"><i class="mdi mdi-clock-outline text-warning fs-5"></i></div>
                </div>
                <p class="text-muted mt-2 mb-0" style="font-size:0.72rem;">{{ $totalPasien > 0 ? round(($menungguBilling/$totalPasien)*100, 1) : 0 }}% dari total pasien</p>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-sm-6 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted text-uppercase fw-bold mb-1" style="font-size:0.72rem;">Transaksi Gantungan</p>
                        <h3 class="fw-black text-dark mb-0">{{ $transaksiGantungan }}</h3>
                    </div>
                    <div class="badge badge-opacity-danger p-2"><i class="mdi mdi-alert-circle-outline text-danger fs-5"></i></div>
                </div>
                <p class="text-muted mt-2 mb-0" style="font-size:0.72rem;">{{ $totalPasien > 0 ? round(($transaksiGantungan/$totalPasien)*100, 1) : 0 }}% dari total pasien</p>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-sm-6 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted text-uppercase fw-bold mb-1" style="font-size:0.72rem;">Total Pasien RI</p>
                        <h3 class="fw-black text-dark mb-0">{{ $totalPasien }}</h3>
                    </div>
                    <div class="badge badge-opacity-primary p-2"><i class="mdi mdi-account-group text-primary fs-5"></i></div>
                </div>
                <p class="text-muted mt-2 mb-0" style="font-size:0.72rem;">Pasien rawat inap aktif</p>
            </div>
        </div>
    </div>
    <div class="col-lg col-md-4 col-sm-6 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted text-uppercase fw-bold mb-1" style="font-size:0.72rem;">Siap Billing</p>
                        <h3 class="fw-black text-success mb-0">{{ collect($patients)->where('status_akhir','Siap Billing')->count() }}</h3>
                    </div>
                    <div class="badge badge-opacity-success p-2"><i class="mdi mdi-clipboard-check text-success fs-5"></i></div>
                </div>
                <a href="{{ route('adru.billing') }}" class="text-success fw-bold" style="font-size:0.72rem;">Proses Billing <i class="mdi mdi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</div>

{{-- FILTER BAR --}}
<div class="card card-rounded shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="fw-bold text-muted" style="font-size:0.78rem;"><i class="mdi mdi-filter-outline text-primary"></i> Filter</span>
            <input type="text" id="filterSearch" class="form-control form-control-sm" style="max-width:220px;" placeholder="Cari nama / No. RM...">
            <select id="filterFloor" class="form-select form-select-sm" style="max-width:160px;">
                <option value="">Semua Lantai</option>
                @foreach($uniqueFloors as $fl)<option value="{{ $fl }}">{{ $fl }}</option>@endforeach
            </select>
            <select id="filterInsurance" class="form-select form-select-sm" style="max-width:180px;">
                <option value="">Semua Cara Bayar</option>
                @foreach($uniqueInsurances as $ins)<option value="{{ $ins }}">{{ $ins }}</option>@endforeach
            </select>
            <select id="filterStatus" class="form-select form-select-sm" style="max-width:160px;">
                <option value="">Semua Status</option>
                @foreach($uniqueStatuses as $st)<option value="{{ $st }}">{{ $st }}</option>@endforeach
            </select>
            <button onclick="resetFilters()" class="btn btn-sm btn-outline-danger">
                <i class="mdi mdi-filter-remove"></i> Reset
            </button>
            <span id="filterInfo" class="text-muted ms-2" style="font-size:0.72rem;"></span>
        </div>
    </div>
</div>

{{-- FLOOR CARDS + ALKES/TINDAKAN WIDGET --}}
<div class="row mb-4">
    {{-- FLOOR CARDS --}}
    <div class="col-xl-8">
        <div id="floorCardsContainer" class="row">
            @php
            $floorGroups = $patients->whereNotIn('status_akhir',['Selesai','Pulang'])->groupBy('floor');
            @endphp
            @foreach($floorGroups->sortKeys() as $floor => $floorPats)
            @php
            $allFloor = $patients->where('floor', $floor);
            $pulangHariIni = $allFloor->whereIn('status_akhir',['Siap Billing','Selesai','Pulang'])->count();
            $discharge = $allFloor->whereIn('status_akhir',['Pulang','Selesai'])->count();
            $meninggal = $allFloor->where('status_akhir','Meninggal')->count();
            @endphp
            <div class="col-md-6 col-xl-4 grid-margin floor-card" data-floor="{{ $floor }}">
                <div class="card card-rounded shadow-sm h-100">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                        <h6 class="mb-0 fw-bold" style="font-size:0.82rem;">{{ $floor }} — Rawat Inap</h6>
                        <span class="badge bg-secondary" style="font-size:0.7rem;">Sisa: {{ count($floorPats) }}</span>
                    </div>
                    <div class="card-body py-2 px-0 border-bottom">
                        <div class="row text-center g-0">
                            <div class="col"><p class="text-muted mb-0" style="font-size:0.65rem;">PULANG HARI INI</p><strong>{{ $pulangHariIni }}</strong></div>
                            <div class="col border-start border-end"><p class="text-muted mb-0" style="font-size:0.65rem;">DISCHARGE</p><strong>{{ $discharge }}</strong></div>
                            <div class="col"><p class="text-muted mb-0" style="font-size:0.65rem;">MENINGGAL</p><strong>{{ $meninggal }}</strong></div>
                        </div>
                    </div>
                    <div class="card-body py-2" style="font-size:0.75rem;">
                        <p class="text-primary fw-bold mb-2" style="font-size:0.72rem;">Rencana Pulang / Pantauan:</p>
                        @foreach($floorPats as $pat)
                        <div class="border rounded p-2 mb-1 patient-row" 
                            style="cursor:pointer;" 
                            onclick="showPatientDetail({{ $pat->id }})"
                            data-name="{{ strtolower($pat->name) }}"
                            data-rm="{{ $pat->rm }}"
                            data-floor="{{ $pat->floor }}"
                            data-insurance="{{ $pat->insurance }}"
                            data-status="{{ $pat->status_akhir }}">
                            <div class="fw-bold text-uppercase" style="font-size:0.72rem;">{{ $pat->name }} ({{ $pat->rm }})</div>
                            <div class="text-muted" style="font-size:0.68rem;">
                                {{ $pat->insurance }} | Rencana Pulang: {{ \Carbon\Carbon::parse($pat->tgl_rencana_pulang)->format('d/m/Y') }}
                                @if($pat->checklist)
                                    | Lab: {{ $pat->checklist->lab_status }}
                                    | Farmasi: {{ $pat->checklist->farmasi_status }}
                                    | Status: <strong>{{ $pat->status_akhir }}</strong>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
            @if($floorGroups->isEmpty())
            <div class="col-12">
                <div class="alert alert-info">Tidak ada data pasien aktif saat ini.</div>
            </div>
            @endif
        </div>
    </div>

    {{-- ALKES & TINDAKAN PENDING --}}
    <div class="col-xl-4">
        <div class="card card-rounded shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-danger">Alkes Belum Diinput</h6>
                <a href="{{ route('adru.gantungan') }}" class="text-primary fw-bold" style="font-size:0.72rem;">Lihat Semua</a>
            </div>
            <div class="card-body py-2" style="max-height:220px;overflow-y:auto;">
                @forelse(collect($alkesList)->take(3) as $item)
                <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded mb-1" style="font-size:0.78rem;">
                    <div>
                        <div class="fw-bold">{{ $item->admission->name ?? 'Pasien' }}</div>
                        <div class="text-danger" style="font-size:0.7rem;">{{ $item->item_name }}</div>
                    </div>
                    <form action="{{ route('adru.resolveOutstanding', $item->id) }}" method="POST">
                        @csrf
                        <button class="btn btn-success btn-sm text-white py-0" style="font-size:0.7rem;">Input</button>
                    </form>
                </div>
                @empty
                <p class="text-muted text-center py-3" style="font-size:0.78rem;">Tidak ada gantungan Alkes.</p>
                @endforelse
            </div>
        </div>
        <div class="card card-rounded shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-warning">Tindakan Belum Diinput</h6>
                <a href="{{ route('adru.gantungan') }}" class="text-primary fw-bold" style="font-size:0.72rem;">Lihat Semua</a>
            </div>
            <div class="card-body py-2" style="max-height:220px;overflow-y:auto;">
                @forelse(collect($tindakanList)->take(3) as $item)
                <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded mb-1" style="font-size:0.78rem;">
                    <div>
                        <div class="fw-bold">{{ $item->admission->name ?? 'Pasien' }}</div>
                        <div class="text-warning" style="font-size:0.7rem;">{{ $item->item_name }}</div>
                    </div>
                    <form action="{{ route('adru.resolveOutstanding', $item->id) }}" method="POST">
                        @csrf
                        <button class="btn btn-success btn-sm text-white py-0" style="font-size:0.7rem;">Input</button>
                    </form>
                </div>
                @empty
                <p class="text-muted text-center py-3" style="font-size:0.78rem;">Tidak ada gantungan Tindakan.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- FULL PATIENT TABLE --}}
<div class="card card-rounded shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">Daftar Pasien Rawat Inap & VK</h6>
        <span id="tableCount" class="text-muted" style="font-size:0.78rem;">Total: {{ $totalPasien }} Pasien</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="pasienTable">
                <thead class="table-light">
                    <tr style="font-size:0.72rem;" class="text-uppercase text-muted">
                        <th class="text-center px-3">No</th>
                        <th class="px-3">RM / Pasien</th>
                        <th class="px-3">Ruang / Lantai</th>
                        <th class="px-3">DPJP</th>
                        <th class="px-3">Cara Bayar</th>
                        <th class="text-center px-3">Lab</th>
                        <th class="text-center px-3">Bedside</th>
                        <th class="text-center px-3">Admin OK</th>
                        <th class="text-center px-3">Farmasi</th>
                        <th class="text-center px-3">Status Akhir</th>
                    </tr>
                </thead>
                <tbody style="font-size:0.78rem;">
                    @php $i = 1; @endphp
                    @foreach($patients->whereNotIn('status_akhir',['Selesai','Pulang']) as $patient)
                    <tr class="patient-row" style="cursor:pointer;" onclick="showPatientDetail({{ $patient->id }})"
                        data-name="{{ strtolower($patient->name) }}"
                        data-rm="{{ $patient->rm }}"
                        data-floor="{{ $patient->floor }}"
                        data-insurance="{{ $patient->insurance }}"
                        data-status="{{ $patient->status_akhir }}">
                        <td class="text-center text-muted px-3">{{ $i++ }}</td>
                        <td class="px-3">
                            <div class="fw-bold text-primary">{{ $patient->rm }}</div>
                            <div class="fw-semibold">{{ $patient->name }}</div>
                        </td>
                        <td class="px-3">{{ $patient->room }} ({{ $patient->floor }})</td>
                        <td class="px-3">{{ $patient->dpjp }}</td>
                        <td class="px-3">{{ $patient->insurance }}</td>
                        <td class="text-center px-3">{!! statusDot($patient->checklist?->lab_status) !!}</td>
                        <td class="text-center px-3">{!! statusDot($patient->checklist?->bedside_status) !!}</td>
                        <td class="text-center px-3">{!! statusDot($patient->checklist?->admin_ok_status) !!}</td>
                        <td class="text-center px-3">{!! statusDot($patient->checklist?->farmasi_status) !!}</td>
                        <td class="text-center px-3">
                            @php
                            $sc = 'bg-warning text-dark';
                            if(in_array($patient->status_akhir,['Siap Billing','Selesai'])) $sc='bg-success text-white';
                            elseif(in_array($patient->status_akhir,['Belum Selesai','Tunda Pulang'])) $sc='bg-danger text-white';
                            @endphp
                            <span class="badge {{ $sc }}">{{ $patient->status_akhir }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Patient Detail Modal --}}
<div class="modal fade" id="patientDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="modalPatientTitle">Rincian Status Discharge</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalPatientBody">
                <div class="text-center py-4"><div class="spinner-border text-primary"></div></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@php
// Helper function for status dots
function statusDot($status) {
    if($status === 'success') return '<span class="badge rounded-pill bg-success" title="Selesai">&nbsp;</span>';
    if($status === 'pending') return '<span class="badge rounded-pill bg-warning" title="Proses">&nbsp;</span>';
    if($status === 'error') return '<span class="badge rounded-pill bg-danger" title="Kendala">&nbsp;</span>';
    return '<span class="text-muted">—</span>';
}

// Build patients JSON for JS
$patientsJson = $patients->map(fn($p) => [
    'id' => $p->id,
    'rm' => $p->rm,
    'name' => $p->name,
    'room' => $p->room,
    'floor' => $p->floor,
    'dpjp' => $p->dpjp,
    'insurance' => $p->insurance,
    'status_akhir' => $p->status_akhir,
    'tgl_rencana_pulang' => $p->tgl_rencana_pulang,
    'jam_rencana_pulang' => $p->jam_rencana_pulang,
    'total_bill' => $p->total_bill,
    'notes' => $p->notes,
    'checklist' => $p->checklist ? [
        'id' => $p->checklist->id,
        'lab_status' => $p->checklist->lab_status,
        'bedside_status' => $p->checklist->bedside_status,
        'admin_ok_status' => $p->checklist->admin_ok_status,
        'farmasi_status' => $p->checklist->farmasi_status,
        'radiologi_status' => $p->checklist->radiologi_status,
        'cot_status' => $p->checklist->cot_status,
        'keterangan_proses' => $p->checklist->keterangan_proses,
    ] : null,
    'outstanding_items' => $p->outstandingItems->map(fn($i) => [
        'id' => $i->id, 'item_name' => $i->item_name, 'unit' => $i->unit, 'status' => $i->status
    ])->values()->toArray()
])->values()->toJson();
@endphp
@stop

@section('js')
<script>
const patientsData = @json(json_decode($patientsJson));

// Filter logic
function applyFilters() {
    const search = document.getElementById('filterSearch').value.toLowerCase();
    const floor = document.getElementById('filterFloor').value;
    const insurance = document.getElementById('filterInsurance').value;
    const status = document.getElementById('filterStatus').value;

    let count = 0;
    document.querySelectorAll('.patient-row').forEach(row => {
        const name = row.dataset.name || '';
        const rm = row.dataset.rm || '';
        const rf = row.dataset.floor || '';
        const ri = row.dataset.insurance || '';
        const rs = row.dataset.status || '';

        const matchSearch = !search || name.includes(search) || rm.includes(search);
        const matchFloor = !floor || rf === floor;
        const matchIns = !insurance || ri === insurance;
        const matchStatus = !status || rs === status;
        const visible = matchSearch && matchFloor && matchIns && matchStatus;

        row.style.display = visible ? '' : 'none';
        if(visible) count++;
    });

    const info = document.getElementById('filterInfo');
    if(search || floor || insurance || status) {
        info.textContent = count + ' hasil ditemukan';
        info.style.display = '';
    } else {
        info.textContent = '';
    }
}

function resetFilters() {
    ['filterSearch','filterFloor','filterInsurance','filterStatus'].forEach(id => {
        const el = document.getElementById(id);
        if(el) el.value = '';
    });
    applyFilters();
}

['filterSearch','filterFloor','filterInsurance','filterStatus'].forEach(id => {
    const el = document.getElementById(id);
    if(el) el.addEventListener('input', applyFilters);
    if(el) el.addEventListener('change', applyFilters);
});

// Patient detail modal
function showPatientDetail(id) {
    const patient = patientsData.find(p => p.id === id);
    if (!patient) return;

    document.getElementById('modalPatientTitle').textContent =
        'Rincian Discharge: ' + patient.name + ' (' + patient.rm + ')';

    const cl = patient.checklist;
    const items = (patient.outstanding_items || []).filter(i => i.status === 'pending');

    const statusDotHtml = (s) => {
        if(s === 'success') return '<span class="badge rounded-pill bg-success">OK</span>';
        if(s === 'pending') return '<span class="badge rounded-pill bg-warning text-dark">Proses</span>';
        if(s === 'error') return '<span class="badge rounded-pill bg-danger">Kendala</span>';
        return '<span class="text-muted">—</span>';
    };

    const checklistHtml = cl ? `
        <div class="row g-2 text-center">
            ${[
                ['Laboratorium', cl.lab_status],
                ['Bedside', cl.bedside_status],
                ['Admin OK (Pagu)', cl.admin_ok_status],
                ['Farmasi', cl.farmasi_status],
                ['Radiologi', cl.radiologi_status],
            ].map(([label, val]) => `
                <div class="col-4">
                    <div class="border rounded p-2">
                        <p class="mb-1 text-muted fw-bold" style="font-size:0.65rem;">${label}</p>
                        ${statusDotHtml(val)}
                        <form action="/adru/checklist/${cl.id}" method="POST" class="mt-1">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="step_key" value="${label.toLowerCase().replace(' ','_').replace('(pagu)','').replace(' ok','_ok').trim()}_status">
                            <input type="hidden" name="status" value="${val === 'success' ? 'pending' : val === 'pending' ? 'error' : val === 'error' ? 'none' : 'success'}">
                            <button type="submit" class="btn btn-outline-secondary btn-sm py-0 mt-1" style="font-size:0.65rem;">Ubah</button>
                        </form>
                    </div>
                </div>
            `).join('')}
            <div class="col-4">
                <div class="border rounded p-2">
                    <p class="mb-1 text-muted fw-bold" style="font-size:0.65rem;">Tindakan COT</p>
                    <span class="badge ${cl.cot_status === 'YA' ? 'bg-purple text-white' : 'bg-secondary'}">${cl.cot_status}</span>
                </div>
            </div>
        </div>
    ` : '<p class="text-muted">Tidak ada data checklist.</p>';

    const outstandingHtml = items.length === 0
        ? '<div class="alert alert-success p-2 mb-0" style="font-size:0.78rem;"><i class="mdi mdi-check-circle me-1"></i> Tidak ada kendala tertunda. Pasien siap diproses.</div>'
        : `<div class="alert alert-danger p-2 mb-2" style="font-size:0.78rem;"><strong>Detail Kendala:</strong>
            <ul class="mb-0 mt-1">
            ${items.map(item => `
                <li class="d-flex justify-content-between align-items-center">
                    <span>${item.item_name} (${item.unit})</span>
                    <form action="/adru/outstanding/${item.id}/resolve" method="POST" class="d-inline ms-2">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <button class="btn btn-success btn-sm py-0 text-white" style="font-size:0.65rem;">Selesaikan</button>
                    </form>
                </li>
            `).join('')}
            </ul></div>`;

    document.getElementById('modalPatientBody').innerHTML = `
        <p class="text-muted mb-3" style="font-size:0.78rem;">
            Ruang: <strong>${patient.room}</strong> | DPJP: <strong>${patient.dpjp}</strong> | Jaminan: <strong>${patient.insurance}</strong>
        </p>
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold text-uppercase mb-3" style="font-size:0.72rem;">Checklist Administrasi</h6>
                ${checklistHtml}
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold text-uppercase mb-3" style="font-size:0.72rem;">Log Kendala & Tindakan Tertunda</h6>
                ${outstandingHtml}
                <div class="d-flex gap-2 mt-3">
                    <form action="/adru/bypass/${patient.id}" method="POST" class="flex-grow-1">
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <button class="btn btn-primary w-100 btn-sm fw-bold" onclick="return confirm('Bypass kendala dan set Siap Billing?')">Bypass & Siap Billing</button>
                    </form>
                </div>
            </div>
        </div>
    `;

    const modal = new bootstrap.Modal(document.getElementById('patientDetailModal'));
    modal.show();
}
</script>
@stop
