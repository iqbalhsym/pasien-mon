@extends('layouts.staradmin')
@section('title', 'Laporan & Grafik')

@section('content_header')
<div class="d-sm-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
  <div>
    <h1 class="h2 text-dark font-weight-bold">Laporan & Grafik</h1>
    <p class="text-muted mb-0">Statistik, analisis lead time, dan grafik kepulangan pasien.</p>
  </div>
</div>
@stop

@section('content')
@php
$dischargedPatients = $patients->whereIn('status_akhir', ['Selesai','Pulang','Meninggal','Rujuk','Pindah Jaminan']);
$totalDischarge = $dischargedPatients->count();
$avgLos = $totalDischarge > 0 ? round($dischargedPatients->avg('length_of_stay'), 1) : 0;
$icuRooms = $patients->whereIn('floor', ['ICU','ICCU','NICU','PICU'])->groupBy('floor')->map->count();

// Delay distribution — patients whose actual discharge was later than planned
$delayDist = [
    '< 1 Jam' => 0,
    '1 - 2 Jam' => 0,
    '2 - 4 Jam' => 0,
    '> 4 Jam' => 0,
];
foreach ($dischargedPatients as $p) {
    if ($p->tgl_rencana_pulang && $p->tgl_aktual_pulang) {
        $planned = \Carbon\Carbon::parse($p->tgl_rencana_pulang);
        $actual  = \Carbon\Carbon::parse($p->tgl_aktual_pulang);
        $diff = $actual->diffInHours($planned, false);
        if ($diff > 0) {
            if ($diff < 1) $delayDist['< 1 Jam']++;
            elseif ($diff <= 2) $delayDist['1 - 2 Jam']++;
            elseif ($diff <= 4) $delayDist['2 - 4 Jam']++;
            else $delayDist['> 4 Jam']++;
        }
    }
}

// Chart data: discharges per day (last 14 days)
$last14Days = collect();
for ($i = 13; $i >= 0; $i--) {
    $date = \Carbon\Carbon::now()->subDays($i)->format('Y-m-d');
    $label = \Carbon\Carbon::now()->subDays($i)->format('d/m');
    $count = $dischargedPatients->filter(fn($p) => 
        $p->tgl_aktual_pulang && \Carbon\Carbon::parse($p->tgl_aktual_pulang)->format('Y-m-d') === $date
    )->count();
    $last14Days->push(['label' => $label, 'count' => $count]);
}

// ICU stats from controller
$icuTotalDischarge = $icuStats['total_discharge'] ?? $dischargedPatients->whereIn('floor',['ICU','ICCU','NICU','PICU'])->count();
$icuAvgLos = $icuStats['avg_los'] ?? 0;
$icuRoomCounts = $icuStats['room_counts'] ?? $icuRooms->toArray();
@endphp

{{-- FILTER PERIODE --}}
<div class="card card-rounded shadow-sm mb-4">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <h6 class="mb-0 fw-bold text-muted text-uppercase" style="font-size:0.72rem;">
                <i class="mdi mdi-calendar-range text-primary"></i> Filter Periode
            </h6>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted" style="font-size:0.78rem;">Mulai:</span>
                <input type="date" id="laporanStart" class="form-control form-control-sm" style="max-width:160px;">
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted" style="font-size:0.78rem;">Sampai:</span>
                <input type="date" id="laporanEnd" class="form-control form-control-sm" style="max-width:160px;">
            </div>
            <button onclick="applyLaporanFilter()" class="btn btn-primary btn-sm">
                <i class="mdi mdi-magnify"></i> Terapkan Filter
            </button>
            <button onclick="resetLaporanFilter()" class="btn btn-outline-secondary btn-sm">
                <i class="mdi mdi-filter-remove"></i> Reset
            </button>
        </div>
    </div>
</div>

{{-- STATISTIK ROW --}}
<div class="row mb-4">
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <p class="text-muted text-uppercase fw-bold mb-1" style="font-size:0.72rem;">Total Discharge</p>
                <h3 class="fw-black text-dark mb-0">{{ $totalDischarge }}</h3>
                <p class="text-muted mt-1 mb-0" style="font-size:0.72rem;">Pasien keluar dari sistem</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <p class="text-muted text-uppercase fw-bold mb-1" style="font-size:0.72rem;">Avg Length of Stay</p>
                <h3 class="fw-black text-primary mb-0">{{ $avgLos }} Hari</h3>
                <p class="text-muted mt-1 mb-0" style="font-size:0.72rem;">Rata-rata rawat inap</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <p class="text-muted text-uppercase fw-bold mb-1" style="font-size:0.72rem;">ICU Total Discharge</p>
                <h3 class="fw-black text-danger mb-0">{{ $icuTotalDischarge }}</h3>
                <p class="text-muted mt-1 mb-0" style="font-size:0.72rem;">Pasien intensif keluar</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <p class="text-muted text-uppercase fw-bold mb-1" style="font-size:0.72rem;">ICU Avg LOS</p>
                <h3 class="fw-black text-info mb-0">{{ $icuAvgLos }} Hari</h3>
                <p class="text-muted mt-1 mb-0" style="font-size:0.72rem;">Rata-rata rawat intensif</p>
            </div>
        </div>
    </div>
</div>

{{-- CHARTS ROW --}}
<div class="row mb-4">
    {{-- DISCHARGE PER HARI CHART --}}
    <div class="col-xl-8 col-md-12 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Jumlah Pasien Discharge per Hari (14 Hari Terakhir)</h6>
            </div>
            <div class="card-body">
                <canvas id="dischargeChart" style="max-height:280px;"></canvas>
            </div>
        </div>
    </div>

    {{-- LEAD TIME DISTRIBUTION CHART --}}
    <div class="col-xl-4 col-md-12 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm h-100">
            <div class="card-header">
                <h6 class="mb-0 fw-bold">Distribusi Keterlambatan (Lead Time)</h6>
            </div>
            <div class="card-body">
                <canvas id="delayChart" style="max-height:280px;"></canvas>
                <div class="mt-3">
                    @foreach($delayDist as $range => $count)
                    <div class="d-flex justify-content-between align-items-center mb-2" style="font-size:0.78rem;">
                        <span>{{ $range }}</span>
                        <span class="fw-bold">{{ $count }} Pasien</span>
                    </div>
                    <div class="progress mb-2" style="height:8px;">
                        @php $pct = ($count > 0 && array_sum($delayDist) > 0) ? round(($count/array_sum($delayDist))*100) : 0; @endphp
                        <div class="progress-bar bg-warning" style="width:{{ $pct }}%"></div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- DISTRIBUTION CHARTS ROW --}}
<div class="row mb-4">
    {{-- BY JAMINAN --}}
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm h-100">
            <div class="card-header">
                <h6 class="mb-0 fw-bold">Distribusi Pasien per Cara Bayar</h6>
            </div>
            <div class="card-body">
                <canvas id="insuranceChart" style="max-height:250px;"></canvas>
            </div>
        </div>
    </div>

    {{-- UNIT PALING SERING TERLAMBAT --}}
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm h-100">
            <div class="card-header">
                <h6 class="mb-0 fw-bold">Unit Paling Sering Terlambat Posting</h6>
                <p class="mb-0 text-muted" style="font-size:0.72rem;">Identifikasi bottleneck unit penghambat billing</p>
            </div>
            <div class="card-body">
                @php
                $unitLambat = [
                    ['name' => 'Farmasi', 'val' => 12, 'pct' => 90, 'class' => 'bg-danger'],
                    ['name' => 'Radiologi', 'val' => 8, 'pct' => 60, 'class' => 'bg-warning'],
                    ['name' => 'Laboratorium', 'val' => 6, 'pct' => 45, 'class' => 'bg-warning'],
                    ['name' => 'COT (Kamar Operasi)', 'val' => 4, 'pct' => 30, 'class' => 'bg-primary'],
                    ['name' => 'ICU', 'val' => 3, 'pct' => 20, 'class' => 'bg-info'],
                ];
                @endphp
                @foreach($unitLambat as $unit)
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:0.78rem;">
                        <span class="fw-bold">{{ $unit['name'] }}</span>
                        <span class="badge bg-secondary">{{ $unit['val'] }} Kasus</span>
                    </div>
                    <div class="progress" style="height:10px;">
                        <div class="progress-bar {{ $unit['class'] }}" style="width:{{ $unit['pct'] }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ICU STATS ROW --}}
<div class="row mb-4">
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-header">
                <h6 class="mb-0 fw-bold">Statistik Ruang ICU/Intensif</h6>
            </div>
            <div class="card-body">
                @if(count($icuRoomCounts) > 0)
                <ul class="list-group list-group-flush">
                    @foreach($icuRoomCounts as $room => $count)
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0" style="font-size:0.82rem;">
                        {{ $room }}
                        <span class="badge bg-primary rounded-pill">{{ $count }}</span>
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="text-muted text-center py-3">Tidak ada data ICU.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-header">
                <h6 class="mb-0 fw-bold">Tindakan COT (Kamar Operasi)</h6>
            </div>
            <div class="card-body">
                @if(count($cotProcedures) > 0)
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr style="font-size:0.72rem;" class="text-uppercase text-muted">
                                <th>RM / Pasien</th>
                                <th>Dokter Operator</th>
                                <th class="text-end">Jasa Operator</th>
                            </tr>
                        </thead>
                        <tbody style="font-size:0.78rem;">
                            @foreach($cotProcedures as $cot)
                            <tr>
                                <td>
                                    <div class="fw-bold text-primary">{{ $cot['rm'] }}</div>
                                    <div>{{ $cot['name'] }}</div>
                                </td>
                                <td>{{ $cot['doctor_operator'] ?? '-' }}</td>
                                <td class="text-end fw-bold">Rp {{ number_format($cot['operator_fee'] ?? 0, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted text-center py-3">Tidak ada data tindakan COT.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// Data from server
const dischargeLabels = @json($last14Days->pluck('label'));
const dischargeCounts = @json($last14Days->pluck('count'));
const delayLabels = @json(array_keys($delayDist));
const delayCounts = @json(array_values($delayDist));

// Prepare insurance distribution
@php
$insuranceDist = $patients->groupBy('insurance')->map->count();
@endphp
const insLabels = @json($insuranceDist->keys());
const insCounts = @json($insuranceDist->values());

// Chart: Discharge per hari
const dischargeCtx = document.getElementById('dischargeChart').getContext('2d');
new Chart(dischargeCtx, {
    type: 'bar',
    data: {
        labels: dischargeLabels,
        datasets: [{
            label: 'Pasien Discharge',
            data: dischargeCounts,
            backgroundColor: 'rgba(16, 185, 129, 0.7)',
            borderColor: 'rgba(16, 185, 129, 1)',
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { mode: 'index', intersect: false }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// Chart: Delay distribution donut
const delayCtx = document.getElementById('delayChart').getContext('2d');
new Chart(delayCtx, {
    type: 'doughnut',
    data: {
        labels: delayLabels,
        datasets: [{
            data: delayCounts,
            backgroundColor: ['#10b981', '#f59e0b', '#f97316', '#ef4444'],
            borderWidth: 2,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } }
    }
});

// Chart: Insurance distribution
const insCtx = document.getElementById('insuranceChart').getContext('2d');
new Chart(insCtx, {
    type: 'bar',
    data: {
        labels: insLabels,
        datasets: [{
            label: 'Jumlah Pasien',
            data: insCounts,
            backgroundColor: [
                'rgba(59,130,246,0.7)', 'rgba(16,185,129,0.7)', 'rgba(245,158,11,0.7)',
                'rgba(239,68,68,0.7)', 'rgba(139,92,246,0.7)', 'rgba(14,165,233,0.7)'
            ],
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

function applyLaporanFilter() {
    const start = document.getElementById('laporanStart').value;
    const end = document.getElementById('laporanEnd').value;
    
    let url = new URL(window.location.href);
    if(start && end) {
        url.searchParams.set('start_date', start);
        url.searchParams.set('end_date', end);
        window.location.href = url.toString();
    } else {
        alert('Mohon pilih tanggal mulai dan sampai.');
    }
}

function resetLaporanFilter() {
    let url = new URL(window.location.href);
    url.searchParams.delete('start_date');
    url.searchParams.delete('end_date');
    window.location.href = url.toString();
}

// Set initial filter values from URL if present
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    if(params.has('start_date')) {
        document.getElementById('laporanStart').value = params.get('start_date');
    }
    if(params.has('end_date')) {
        document.getElementById('laporanEnd').value = params.get('end_date');
    }
});
</script>
@stop
