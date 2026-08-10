@extends('layouts.staradmin')
@section('title', 'Monitoring Billing')

@section('content_header')
<div class="d-sm-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
  <div>
    <h1 class="h2 text-dark font-weight-bold">Monitoring Billing</h1>
    <p class="text-muted mb-0">Daftar pasien yang telah pulang dari bed management RSUI.</p>
  </div>
  <div>
    <a href="{{ route('adru.export', 'billing') }}" class="btn btn-success text-white"><i class="mdi mdi-microsoft-excel me-1"></i> Export CSV</a>
  </div>
</div>
@stop

@section('content')
@php
// Prioritize API data if available
if (isset($apiDischargedPatients) && count($apiDischargedPatients) > 0) {
    $dischargedPatients = collect($apiDischargedPatients);
    $isApiData = true;
} else {
    $dischargedPatients = $patients->whereIn('status_akhir', ['Selesai','Pulang','Meninggal','Rujuk','Pindah Jaminan']);
    $isApiData = false;
}

$totalCount = $dischargedPatients->count();
$uniqueMonFloors = $dischargedPatients->pluck('floor')->filter()->unique()->sort()->values();
$uniqueMonIns = $dischargedPatients->pluck('insurance')->filter()->unique()->sort()->values();
@endphp

{{-- SUMMARY CARDS --}}
<div class="row mb-4">
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <p class="text-muted text-uppercase fw-bold mb-1" style="font-size:0.72rem;">Total Pasien Discharge</p>
                <h3 class="fw-black text-dark mb-0">{{ $totalCount }}</h3>
                <p class="text-muted mb-0" style="font-size:0.7rem;">
                    @if($isApiData)
                        <span class="badge bg-success">Live — Bed Monitoring API</span>
                    @else
                        <span class="badge bg-secondary">Data Lokal</span>
                    @endif
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <p class="text-muted text-uppercase fw-bold mb-1" style="font-size:0.72rem;">Jaminan BPJS</p>
                <h3 class="fw-black text-primary mb-0">
                    {{ $dischargedPatients->filter(fn($p) => str_contains(strtolower($p->insurance ?? ''), 'bpjs'))->count() }}
                </h3>
                <p class="text-muted mb-0" style="font-size:0.7rem;">pasien dari total {{ $totalCount }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <p class="text-muted text-uppercase fw-bold mb-1" style="font-size:0.72rem;">Lantai Terbanyak</p>
                @php
                    $topFloor = $dischargedPatients->filter(fn($p) => !empty($p->floor))->groupBy('floor')->sortByDesc->count()->keys()->first() ?? '-';
                    $topFloorCount = $dischargedPatients->where('floor', $topFloor)->count();
                @endphp
                <h3 class="fw-black text-info mb-0">{{ $topFloor }}</h3>
                <p class="text-muted mb-0" style="font-size:0.7rem;">{{ $topFloorCount }} pasien</p>
            </div>
        </div>
    </div>
</div>

{{-- MAIN TABLE SECTION --}}
<div class="card card-rounded shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold">Daftar Pasien Discharge ({{ $totalCount }})</h6>
        <div class="d-flex gap-2 flex-wrap">
            {{-- Filters --}}
            <input type="text" id="monSearch" class="form-control form-control-sm" style="width:200px;" placeholder="Cari nama / No. RM...">
            <select id="monFloor" class="form-select form-select-sm" style="width:160px;">
                <option value="">Semua Lantai</option>
                @foreach($uniqueMonFloors as $fl)<option value="{{ $fl }}">{{ $fl }}</option>@endforeach
            </select>
            <select id="monJaminan" class="form-select form-select-sm" style="width:190px;">
                <option value="">Semua Jaminan</option>
                @foreach($uniqueMonIns as $ins)<option value="{{ $ins }}">{{ $ins }}</option>@endforeach
            </select>
            <input type="date" id="monDate" class="form-control form-control-sm" style="width:160px;" title="Filter Tanggal Pulang">
            <select id="monLimit" class="form-select form-select-sm" style="width:110px;">
                <option value="10">10 Baris</option>
                <option value="20">20 Baris</option>
                <option value="50">50 Baris</option>
                <option value="100">100 Baris</option>
            </select>
            <button onclick="resetMonFilters()" class="btn btn-sm btn-outline-danger" title="Reset Filter">
                <i class="mdi mdi-filter-remove"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:520px;overflow-y:auto;">
            <table class="table table-hover align-middle mb-0" id="monitoringTable">
                <thead class="table-light sticky-top">
                    <tr style="font-size:0.72rem;" class="text-uppercase text-muted">
                        <th class="px-3">No. RM / Pasien</th>
                        <th class="px-3">Diagnosa</th>
                        <th class="text-center px-3">Lantai / Ruang</th>
                        <th class="text-center px-3">Jaminan / Kelas</th>
                        <th class="text-center px-3">Waktu Pulang</th>
                        <th class="text-center px-3">DPJP</th>
                    </tr>
                </thead>
                <tbody style="font-size:0.78rem;" id="monitoringBody">
                    @forelse($dischargedPatients as $patient)
                    <tr class="mon-row"
                        data-name="{{ strtolower($patient->name) }}"
                        data-rm="{{ strtolower($patient->rm ?? '') }}"
                        data-floor="{{ $patient->floor }}"
                        data-insurance="{{ $patient->insurance }}"
                        data-date="{{ $patient->tgl_aktual_pulang ? \Carbon\Carbon::parse($patient->tgl_aktual_pulang)->format('Y-m-d') : '' }}">
                        <td class="px-3">
                            <div class="fw-bold text-primary" style="font-size:0.78rem;">{{ $patient->rm ?? '-' }}</div>
                            <div class="fw-semibold">{{ $patient->name }}</div>
                            @isset($patient->age)
                            <div class="text-muted" style="font-size:0.68rem;">{{ $patient->age }} Tahun</div>
                            @endisset
                        </td>
                        <td class="px-3" style="max-width:220px;">
                            <div style="font-size:0.75rem;">{{ $patient->diagnosa ?? '-' }}</div>
                        </td>
                        <td class="text-center px-3">
                            <div class="fw-semibold">{{ $patient->floor ?? '-' }}</div>
                            <div class="text-muted" style="font-size:0.7rem;">{{ $patient->room ?? '-' }}</div>
                        </td>
                        <td class="text-center px-3">
                            <span class="badge {{ str_contains(strtolower($patient->insurance ?? ''), 'bpjs') ? 'bg-primary' : 'bg-secondary' }}">
                                {{ $patient->insurance ?? '-' }}
                            </span>
                            @if($isApiData && isset($patient->hak_kelas))
                            <div class="text-muted" style="font-size:0.68rem;">{{ $patient->hak_kelas }}</div>
                            @endif
                        </td>
                        <td class="text-center px-3">
                            @if($patient->tgl_aktual_pulang)
                                <div class="fw-semibold">{{ \Carbon\Carbon::parse($patient->tgl_aktual_pulang)->format('d-m-Y') }}</div>
                                <div class="text-muted" style="font-size:0.68rem;">{{ \Carbon\Carbon::parse($patient->tgl_aktual_pulang)->format('H:i') }} WIB</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center px-3 text-muted" style="font-size:0.75rem;">
                            {{ !empty($patient->dpjp) ? $patient->dpjp : '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr id="no-data-row">
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="mdi mdi-alert-circle-outline" style="font-size:2rem;"></i>
                            <p class="mt-2 mb-0">Tidak ada data pasien discharge.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
            <span id="monPagInfo" class="text-muted" style="font-size:0.72rem;"></span>
            <div class="d-flex gap-2">
                <button id="monPrevBtn" onclick="changePage(-1)" class="btn btn-sm btn-outline-secondary">
                    <i class="mdi mdi-chevron-left"></i> Sebelumnya
                </button>
                <span id="monPageInfo" class="align-self-center px-2 fw-semibold" style="font-size:0.78rem;"></span>
                <button id="monNextBtn" onclick="changePage(1)" class="btn btn-sm btn-outline-secondary">
                    Selanjutnya <i class="mdi mdi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script>
let monPage = 1;

function getMonRows() {
    const search = (document.getElementById('monSearch').value || '').toLowerCase().trim();
    const floor = document.getElementById('monFloor').value;
    const jaminan = document.getElementById('monJaminan').value;
    const date = document.getElementById('monDate').value;

    return Array.from(document.querySelectorAll('.mon-row')).filter(row => {
        const matchSearch = !search
            || row.dataset.name.includes(search)
            || row.dataset.rm.includes(search);
        const matchFloor = !floor || row.dataset.floor === floor;
        const matchJaminan = !jaminan || row.dataset.insurance === jaminan;
        const matchDate = !date || row.dataset.date.startsWith(date);
        return matchSearch && matchFloor && matchJaminan && matchDate;
    });
}

function renderMonPage() {
    const limit = parseInt(document.getElementById('monLimit').value);
    const rows = getMonRows();
    const total = rows.length;
    const start = (monPage - 1) * limit;
    const end = Math.min(start + limit, total);

    // Hide ALL rows first
    Array.from(document.querySelectorAll('.mon-row')).forEach(r => r.style.display = 'none');
    // Show only current page matching rows
    rows.forEach((r, i) => {
        r.style.display = (i >= start && i < end) ? '' : 'none';
    });

    // No-data row
    const noData = document.getElementById('no-data-row');
    if (noData) noData.style.display = 'none';

    document.getElementById('monPagInfo').textContent = total > 0
        ? `Menampilkan ${start + 1} – ${end} dari ${total} data`
        : 'Tidak ada data';
    document.getElementById('monPageInfo').textContent = `Halaman ${monPage} dari ${Math.max(1, Math.ceil(total / limit))}`;
    document.getElementById('monPrevBtn').disabled = monPage === 1;
    document.getElementById('monNextBtn').disabled = monPage >= Math.ceil(total / limit) || total === 0;
}

function changePage(dir) {
    const limit = parseInt(document.getElementById('monLimit').value);
    const total = getMonRows().length;
    const maxPage = Math.max(1, Math.ceil(total / limit));
    monPage = Math.max(1, Math.min(maxPage, monPage + dir));
    renderMonPage();
}

function applyMonFilters() {
    monPage = 1;
    renderMonPage();
}

function resetMonFilters() {
    ['monSearch', 'monFloor', 'monJaminan', 'monDate'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('monLimit').value = '10';
    monPage = 1;
    renderMonPage();
}

['monSearch', 'monFloor', 'monJaminan', 'monDate', 'monLimit'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', applyMonFilters);
    document.getElementById(id)?.addEventListener('change', applyMonFilters);
});

// Init on load
document.addEventListener('DOMContentLoaded', renderMonPage);
renderMonPage();
</script>
@stop
