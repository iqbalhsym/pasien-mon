@extends('layouts.staradmin')

@section('title', 'Respon e-Konsul DPJP')

@section('content_header')
<div class="d-sm-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size: 0.85rem; padding: 0; background: none;">
                    <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted">Dashboard Mutu</a></li>
                    <li class="breadcrumb-item active fw-bold" aria-current="page">Respon e-Konsul DPJP</li>
                </ol>
            </nav>
            <h2 class="h3 font-weight-bold mb-1 text-dark d-flex align-items-center">
                Respon e-Konsul DPJP dalam 24 Jam
                <i class="mdi mdi-information-outline text-muted fs-5 ms-2" title="Persentase e-konsul yang direspon DPJP dalam waktu ≤ 24 jam sejak permintaan dibuat"></i>
            </h2>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">Persentase e-konsul yang direspon DPJP dalam waktu ≤ 24 jam sejak permintaan dibuat</p>
        </div>
    </div>
    <div class="d-flex gap-2 mt-3 mt-sm-0 align-items-center">
        <span class="text-muted me-3" style="font-size: 0.85rem;">Data terakhir: {{ now()->format('d F Y H:i') }} WIB <i class="mdi mdi-refresh ms-1" style="cursor:pointer;" onclick="location.reload();"></i></span>
        <button class="btn btn-outline-secondary bg-white btn-sm fw-bold shadow-sm py-2 px-3">
            <i class="mdi mdi-export me-1"></i> Export
        </button>
    </div>
</div>
@stop

@section('content')

<style>
    .card-mutu {
        border-radius: 12px;
        box-shadow: 0px 4px 16px rgba(0, 0, 0, 0.04);
        border: 1px solid #f0f0f0;
        background: #fff;
    }
    .text-success-dark { color: #198754; }
    .text-danger-dark { color: #dc3545; }
    .text-primary-dark { color: #0d6efd; }
    
    /* CSS Donut Chart for Kepatuhan */
    .progress-circle {
        position: relative;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: conic-gradient(
            #198754 {{ $persentaseKepatuhan }}%, 
            #e9ecef {{ $persentaseKepatuhan }}% 100%
        );
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .progress-circle::before {
        content: "";
        position: absolute;
        width: 60px;
        height: 60px;
        background-color: white;
        border-radius: 50%;
    }
    
    /* Simple icon circles */
    .icon-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
    }
    .icon-circle-success { background: #e8f5e9; color: #198754; }
    .icon-circle-danger { background: #ffebee; color: #dc3545; }
    .icon-circle-primary { background: #e3f2fd; color: #0d6efd; }

    .filter-label-konsul {
        font-size: 0.8rem;
        font-weight: 600;
        color: #6c757d;
        margin-bottom: 0.2rem;
        display: block;
    }
    
</style>

<!-- FILTERS -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-mutu">
            <div class="card-body p-3">
                <form action="{{ route('mutu.respon-konsul') }}" method="GET" id="filterFormKonsul" class="d-flex flex-wrap gap-3 align-items-end">
                    <div style="min-width: 150px;">
                        <label class="filter-label-konsul"><i class="mdi mdi-calendar-range me-1"></i>Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control fw-bold" style="font-size: 0.9rem;" value="{{ $dateFrom }}" onchange="this.form.submit();">
                    </div>
                    
                    <div style="min-width: 150px;">
                        <label class="filter-label-konsul"><i class="mdi mdi-calendar-range me-1"></i>Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control fw-bold" style="font-size: 0.9rem;" value="{{ $dateTo }}" onchange="this.form.submit();">
                    </div>

                    <div style="min-width: 200px;">
                        <label class="filter-label-konsul"><i class="mdi mdi-layers-outline me-1"></i>Lantai / Ruangan</label>
                        <select name="floor" class="form-select fw-bold" style="font-size: 0.9rem;" onchange="this.form.submit();">
                            <option value="">Semua Lantai</option>
                            @foreach($floors as $f)
                                <option value="{{ $f->name }}" {{ $selectedFloor == $f->name ? 'selected' : '' }}>
                                    {{ is_numeric($f->name) ? 'Lantai ' . $f->name : $f->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div style="min-width: 180px;">
                        <label class="filter-label-konsul"><i class="mdi mdi-doctor me-1"></i>Spesialis</label>
                        <select name="spesialis" class="form-select fw-bold" style="font-size: 0.9rem;" onchange="this.form.submit();">
                            <option value="">Semua Spesialis</option>
                            @foreach(['Penyakit Dalam', 'Obstetri & Ginekologi', 'Bedah', 'Jantung', 'Anestesi', 'Anak'] as $sp)
                                <option value="{{ $sp }}" {{ $selectedSpesialis == $sp ? 'selected' : '' }}>{{ $sp }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ms-auto d-flex gap-2">
                        @if($selectedFloor || $selectedSpesialis || $dateFrom !== now()->startOfMonth()->toDateString() || $dateTo !== now()->toDateString())
                            <a href="{{ route('mutu.respon-konsul') }}" class="btn btn-light border bg-white fw-bold shadow-sm" style="font-size: 0.9rem;">
                                <i class="mdi mdi-refresh me-1"></i> Reset Filter
                            </a>
                        @else
                            <button type="button" class="btn btn-light border bg-white fw-bold shadow-sm disabled" style="font-size: 0.9rem;">
                                <i class="mdi mdi-refresh me-1"></i> Reset Filter
                            </button>
                        @endif
                    </div>
                </form>

                @if($selectedFloor || $selectedSpesialis || $dateFrom !== now()->startOfMonth()->toDateString() || $dateTo !== now()->toDateString())
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        <span class="badge bg-secondary text-white fw-bold px-2 py-1" style="font-size: 0.78rem;">
                            <i class="mdi mdi-calendar me-1"></i>Periode: {{ date('d/m/Y', strtotime($dateFrom)) }} - {{ date('d/m/Y', strtotime($dateTo)) }}
                        </span>
                        @if($selectedFloor)
                            <span class="badge bg-primary text-white fw-bold px-2 py-1" style="font-size: 0.78rem;">
                                <i class="mdi mdi-layers-outline me-1"></i>Lantai: {{ is_numeric($selectedFloor) ? 'Lantai ' . $selectedFloor : $selectedFloor }}
                            </span>
                        @endif
                        @if($selectedSpesialis)
                            <span class="badge bg-success text-white fw-bold px-2 py-1" style="font-size: 0.78rem;">
                                <i class="mdi mdi-doctor me-1"></i>Spesialis: {{ $selectedSpesialis }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>


<!-- SCORECARDS -->
<div class="row mb-4 g-3">
    <!-- Main Score -->
    <div class="col-md-3">
        <div class="card card-mutu h-100">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-dark fw-bold mb-1">Kepatuhan Respon ≤ 24 Jam</h6>
                    <h2 class="fw-bolder mb-0 text-success-dark" style="font-size: 2.8rem;">{{ $persentaseKepatuhan }}%</h2>
                    <p class="text-muted fw-bold mb-1" style="font-size: 0.85rem;">{{ $kurang24Jam }} dari {{ $totalKonsul }} e-konsul</p>
                    <p class="text-success fw-bold mb-0" style="font-size: 0.75rem;"><i class="mdi mdi-arrow-up"></i> 4,8% dari periode sebelumnya</p>
                </div>
                <div class="progress-circle"></div>
            </div>
        </div>
    </div>
    
    <!-- <= 24 Jam -->
    <div class="col-md-3">
        <div class="card card-mutu h-100">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-dark fw-bold mb-1">E-Konsul ≤ 24 Jam</h6>
                    <h2 class="fw-bolder mb-0 text-success-dark" style="font-size: 2.5rem;">{{ $kurang24Jam }}</h2>
                    <p class="text-muted fw-bold mb-0" style="font-size: 0.9rem;">e-konsul</p>
                </div>
                <div class="icon-circle icon-circle-success">
                    <i class="mdi mdi-check-circle-outline"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- > 24 Jam -->
    <div class="col-md-3">
        <div class="card card-mutu h-100">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-dark fw-bold mb-1">E-Konsul > 24 Jam</h6>
                    <h2 class="fw-bolder mb-0 text-danger-dark" style="font-size: 2.5rem;">{{ $lebih24Jam }}</h2>
                    <p class="text-muted fw-bold mb-0" style="font-size: 0.9rem;">e-konsul</p>
                </div>
                <div class="icon-circle icon-circle-danger">
                    <i class="mdi mdi-alert-circle-outline"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total & Target -->
    <div class="col-md-3">
        <div class="row h-100 g-3">
            <div class="col-12 h-50">
                <div class="card card-mutu h-100">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-dark fw-bold mb-0" style="font-size: 0.85rem;">Total E-Konsul</h6>
                            <h3 class="fw-bolder mb-0 text-primary" style="font-size: 1.8rem;">{{ $totalKonsul }} <span class="text-muted fs-6">e-konsul</span></h3>
                        </div>
                        <div class="icon-circle icon-circle-primary" style="width: 45px; height: 45px; font-size: 1.2rem;">
                            <i class="mdi mdi-comment-multiple-outline"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 h-50">
                <div class="card card-mutu h-100 bg-white">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-dark fw-bold mb-0" style="font-size: 0.85rem;">Target</h6>
                            <h3 class="fw-bolder mb-0 text-primary" style="font-size: 1.8rem;">≥ 95%</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CHARTS & TABLE SUMMARY -->
<div class="row mb-4 g-3">
    <!-- Tabel Summary -->
    <div class="col-lg-6">
        <div class="card card-mutu h-100">
            <div class="card-body">
                <h5 class="fw-bold text-dark mb-3">Kepatuhan Respon e-Konsul per DPJP (Pemberi Respon)</h5>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead style="background: #f8f9fa;">
                            <tr>
                                <th>No</th>
                                <th>DPJP (Pemberi Respon)</th>
                                <th>Spesialis</th>
                                <th class="text-center">Total e-Konsul</th>
                                <th class="text-center">≤ 24 Jam</th>
                                <th class="text-center">> 24 Jam</th>
                                <th class="text-center">Kepatuhan</th>
                                <th class="text-center">Trend</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dpjpStats as $index => $stat)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="fw-bold text-dark">{{ $stat['dpjp'] }}</td>
                                <td>{{ $stat['spesialis'] }}</td>
                                <td class="text-center">{{ $stat['total'] }}</td>
                                <td class="text-center">{{ $stat['kurang24'] }}</td>
                                <td class="text-center">{{ $stat['lebih24'] }}</td>
                                <td class="text-center fw-bold {{ $stat['kepatuhan'] >= 95 ? 'text-success' : ($stat['kepatuhan'] >= 85 ? 'text-warning' : 'text-danger') }}">
                                    {{ $stat['kepatuhan'] }}%
                                </td>
                                <td class="text-center fw-bold">
                                    @if($stat['trend'] == 'up')
                                        <i class="mdi mdi-arrow-top-right text-success"></i>
                                    @elseif($stat['trend'] == 'down')
                                        <i class="mdi mdi-arrow-bottom-right text-danger"></i>
                                    @else
                                        <i class="mdi mdi-swap-horizontal text-warning"></i>
                                    @endif
                                </td>
                                <td><button class="btn btn-outline-primary btn-sm px-2 py-1" style="font-size: 0.75rem;">Detail</button></td>
                            </tr>
                            @endforeach
                            <!-- Total Row -->
                            <tr style="background: #f8f9fa; font-weight: bold;">
                                <td colspan="3" class="text-center">Total</td>
                                <td class="text-center">{{ $totalKonsul }}</td>
                                <td class="text-center">{{ $kurang24Jam }}</td>
                                <td class="text-center">{{ $lebih24Jam }}</td>
                                <td class="text-center text-success">{{ $persentaseKepatuhan }}%</td>
                                <td></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Line Chart Trend -->
    <div class="col-lg-6">
        <div class="card card-mutu h-100">
            <div class="card-body">
                <h5 class="fw-bold text-dark mb-4">Tren Kepatuhan Respon e-Konsul</h5>
                
                <div style="height: 300px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- DETAILS TABLE -->
<div class="row">
    <div class="col-12">
        <div class="card card-mutu">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold text-primary mb-0">Daftar e-Konsul > 24 Jam <span class="text-danger">({{ count($daftarLebih24Jam) }} e-konsul)</span></h5>
                    
                    <div class="d-flex gap-2">
                        <div class="input-group input-group-sm border bg-white rounded" style="width: 250px;">
                            <input type="text" class="form-control border-0" placeholder="Cari nama pasien / No. RM / DPJP">
                            <span class="input-group-text bg-white border-0"><i class="mdi mdi-magnify text-muted"></i></span>
                        </div>
                        <button class="btn btn-outline-secondary bg-white btn-sm fw-bold">
                            <i class="mdi mdi-export me-1"></i> Export
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle border">
                        <thead style="background: #f8f9fa;">
                            <tr>
                                <th class="text-center border-end">No</th>
                                <th class="text-center">Tanggal Order</th>
                                <th class="text-center">Jam Order</th>
                                <th>No. RM</th>
                                <th>Nama Pasien<br><span style="font-size: 0.75rem; font-weight: normal;">Unit / Ruangan</span></th>
                                <th>Konsul ke DPJP<br><span style="font-size: 0.75rem; font-weight: normal;">Spesialis</span></th>
                                <th>DPJP Pemberi Respon<br><span style="font-size: 0.75rem; font-weight: normal;">Spesialis</span></th>
                                <th class="text-center">Jam Respon</th>
                                <th class="text-center">Lama Respon</th>
                                <th class="text-center">Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($daftarLebih24Jam as $idx => $p)
                            <tr>
                                <td class="text-center border-end">{{ $idx + 1 }}</td>
                                <td class="text-center">{{ $p['tgl_order'] }}</td>
                                <td class="text-center">{{ $p['jam_order'] }}</td>
                                <td class="fw-bold">{{ $p['no_rm'] }}</td>
                                <td>
                                    <div class="text-primary fw-bold">{{ $p['nama'] }}</div>
                                    <div class="text-muted" style="font-size: 0.8rem;">{{ $p['ruangan'] }}</div>
                                </td>
                                <td>
                                    <div class="text-dark">{{ $p['konsul_ke'] }}</div>
                                    <div class="text-muted" style="font-size: 0.8rem;">({{ $p['konsul_spesialis'] }})</div>
                                </td>
                                <td>
                                    <div class="text-dark">{{ $p['respon_dari'] }}</div>
                                    <div class="text-muted" style="font-size: 0.8rem;">({{ $p['respon_spesialis'] }})</div>
                                </td>
                                <td class="text-center">
                                    {{ $p['tgl_respon'] }}<br>{{ $p['jam_respon'] }}
                                </td>
                                <td class="text-center text-danger fw-bold">{{ $p['lama_respon'] }}</td>
                                <td class="text-center">
                                    <span class="badge bg-danger"> > 24 Jam </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-light btn-sm border py-1 px-2 text-muted"><i class="mdi mdi-dots-vertical"></i></button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <p class="text-muted mt-3 mb-0" style="font-size: 0.8rem;">
                    <i class="mdi mdi-information-outline"></i> <strong>Perhitungan:</strong> Lama respon = Jam respon - Jam order e-konsul. Respon dianggap sah jika DPJP memberikan jawaban/feedback di sistem.
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Chart JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@2.1.0/dist/chartjs-plugin-annotation.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const labels = {!! json_encode($trendLabels) !!};
    const dataValues = {!! json_encode($trendData) !!};
    
    const ctx = document.getElementById('trendChart').getContext('2d');
    
    const dataLabelsPlugin = {
        id: 'dataLabels',
        afterDatasetsDraw(chart, args, options) {
            const { ctx } = chart;
            chart.data.datasets.forEach((dataset, i) => {
                const meta = chart.getDatasetMeta(i);
                meta.data.forEach((point, index) => {
                    const data = dataset.data[index];
                    ctx.fillStyle = '#333';
                    ctx.font = 'bold 11px Manrope, sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';
                    ctx.fillText(data + '%', point.x, point.y - 10);
                });
            });
        }
    };

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Kepatuhan Harian (%)',
                data: dataValues,
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#198754',
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        stepSize: 20,
                        font: { family: 'Manrope', size: 11 }
                    },
                    grid: { color: '#f0f0f0', drawBorder: false }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: {
                        font: { family: 'Manrope', size: 10 }
                    }
                }
            },
            plugins: {
                legend: { 
                    position: 'bottom',
                    labels: { usePointStyle: true, boxWidth: 8, font: { family: 'Manrope', size: 11 } }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.raw + '%';
                        }
                    }
                },
                annotation: {
                    annotations: {
                        line1: {
                            type: 'line',
                            yMin: 95,
                            yMax: 95,
                            borderColor: 'rgba(220, 53, 69, 0.8)',
                            borderWidth: 1.5,
                            borderDash: [5, 5],
                            label: {
                                content: 'Target 95%',
                                display: true,
                                position: 'end',
                                backgroundColor: 'transparent',
                                color: '#dc3545',
                                font: { weight: 'bold', size: 11 }
                            }
                        }
                    }
                }
            }
        },
        plugins: [dataLabelsPlugin]
    });
});
</script>

@stop
