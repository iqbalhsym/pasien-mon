@extends('layouts.staradmin')

@section('title', 'Kepatuhan Visit DPJP')

@section('content_header')
<div class="d-sm-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1" style="font-size: 0.85rem; padding: 0; background: none;">
                    <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted">Dashboard Mutu</a></li>
                    <li class="breadcrumb-item active fw-bold" aria-current="page">Kepatuhan Visit DPJP</li>
                </ol>
            </nav>
            <h2 class="h3 font-weight-bold mb-1 text-dark d-flex align-items-center">
                Kepatuhan Visit DPJP
                <i class="mdi mdi-information-outline text-muted fs-5 ms-2" title="Persentase pasien yang telah dikunjungi (visite) oleh DPJP sesuai ketentuan"></i>
            </h2>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">Persentase pasien yang telah dikunjungi (visite) oleh DPJP sesuai ketentuan</p>
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
    .text-warning-dark { color: #ffc107; }
    
    .donut-chart-container {
        position: relative;
        width: 80px;
        height: 80px;
    }
    
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
    
    .filter-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #6c757d;
        margin-bottom: 0.2rem;
    }
</style>

<!-- FILTERS -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-mutu">
            <div class="card-body p-3">
                <form action="{{ route('mutu.kepatuhan-visit') }}" method="GET" id="filterFormVisit" class="d-flex flex-wrap gap-3 align-items-end">
                    <div style="min-width: 150px;">
                        <label class="filter-label"><i class="mdi mdi-calendar-range me-1"></i>Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control fw-bold" style="font-size: 0.9rem;" value="{{ $dateFrom }}" onchange="this.form.submit();">
                    </div>
                    
                    <div style="min-width: 150px;">
                        <label class="filter-label"><i class="mdi mdi-calendar-range me-1"></i>Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control fw-bold" style="font-size: 0.9rem;" value="{{ $dateTo }}" onchange="this.form.submit();">
                    </div>

                    <div style="min-width: 160px;">
                        <label class="filter-label"><i class="mdi mdi-home-variant me-1"></i>Wings / Bagian</label>
                        <select name="wing" id="wingSelectVisit" class="form-select fw-bold" style="font-size: 0.9rem;" onchange="this.form.submit();">
                            <option value="">Semua Wings</option>
                            @foreach($wings as $w)
                                <option value="{{ $w->name }}" {{ $selectedWing == $w->name ? 'selected' : '' }}>{{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="min-width: 160px;">
                        <label class="filter-label"><i class="mdi mdi-door me-1"></i>Ruangan</label>
                        <select name="room" id="roomSelectVisit" class="form-select fw-bold" style="font-size: 0.9rem;" onchange="this.form.submit();">
                            <option value="">Semua Ruangan</option>
                            @foreach($selectedRooms as $r)
                                <option value="{{ $r->name }}" {{ $selectedRoom == $r->name ? 'selected' : '' }}>Kamar {{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="min-width: 180px;">
                        <label class="filter-label"><i class="mdi mdi-doctor me-1"></i>Spesialis</label>
                        <select name="spesialis" class="form-select fw-bold" style="font-size: 0.9rem;" onchange="this.form.submit();">
                            <option value="">Semua Spesialis</option>
                            @foreach(['Penyakit Dalam', 'Obstetri & Ginekologi', 'Bedah', 'Jantung', 'Anestesi', 'Anak'] as $sp)
                                <option value="{{ $sp }}" {{ $selectedSpesialis == $sp ? 'selected' : '' }}>{{ $sp }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ms-auto d-flex gap-2">
                        @if($selectedWing || $selectedRoom || $selectedSpesialis || $dateFrom !== now()->startOfMonth()->toDateString() || $dateTo !== now()->toDateString())
                            <a href="{{ route('mutu.kepatuhan-visit') }}" class="btn btn-light border bg-white fw-bold shadow-sm" style="font-size: 0.9rem;">
                                <i class="mdi mdi-refresh me-1"></i> Reset Filter
                            </a>
                        @else
                            <button type="button" class="btn btn-light border bg-white fw-bold shadow-sm disabled" style="font-size: 0.9rem;">
                                <i class="mdi mdi-refresh me-1"></i> Reset Filter
                            </button>
                        @endif
                    </div>
                </form>

                @if($selectedWing || $selectedRoom || $selectedSpesialis || $dateFrom !== now()->startOfMonth()->toDateString() || $dateTo !== now()->toDateString())
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        <span class="badge bg-secondary text-white fw-bold px-2 py-1" style="font-size: 0.78rem;">
                            <i class="mdi mdi-calendar me-1"></i>Periode: {{ date('d/m/Y', strtotime($dateFrom)) }} - {{ date('d/m/Y', strtotime($dateTo)) }}
                        </span>
                        @if($selectedWing)
                            <span class="badge bg-primary text-white fw-bold px-2 py-1" style="font-size: 0.78rem;">
                                <i class="mdi mdi-home-variant me-1"></i>Wing: {{ $selectedWing }}
                            </span>
                        @endif
                        @if($selectedRoom)
                            <span class="badge bg-info text-white fw-bold px-2 py-1" style="font-size: 0.78rem;">
                                <i class="mdi mdi-door me-1"></i>Ruangan: Kamar {{ $selectedRoom }}
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
                    <h6 class="text-dark fw-bold mb-1">Kepatuhan Visit DPJP</h6>
                    <h2 class="fw-bolder mb-0 text-success-dark" style="font-size: 2.8rem;">{{ $persentaseKepatuhan }}%</h2>
                    <p class="text-muted fw-bold mb-1" style="font-size: 0.85rem;">{{ $sudahVisit }} / {{ $totalPasien }} pasien</p>
                    <p class="text-success fw-bold mb-0" style="font-size: 0.75rem;"><i class="mdi mdi-arrow-up"></i> 5,6% dari periode sebelumnya</p>
                </div>
                <div class="progress-circle"></div>
            </div>
        </div>
    </div>
    
    <!-- Sudah Visit -->
    <div class="col-md-3">
        <div class="card card-mutu h-100">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-dark fw-bold mb-1">Sudah Visit</h6>
                    <h2 class="fw-bolder mb-0 text-success-dark" style="font-size: 2.5rem;">{{ $sudahVisit }}</h2>
                    <p class="text-muted fw-bold mb-0" style="font-size: 0.9rem;">pasien</p>
                </div>
                <div class="icon-circle icon-circle-success">
                    <i class="mdi mdi-account-check-outline"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Belum Visit -->
    <div class="col-md-3">
        <div class="card card-mutu h-100">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-dark fw-bold mb-1">Belum Visit</h6>
                    <h2 class="fw-bolder mb-0 text-danger-dark" style="font-size: 2.5rem;">{{ $belumVisit }}</h2>
                    <p class="text-muted fw-bold mb-0" style="font-size: 0.9rem;">pasien</p>
                </div>
                <div class="icon-circle icon-circle-danger">
                    <i class="mdi mdi-account-alert-outline"></i>
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
                            <h6 class="text-dark fw-bold mb-0" style="font-size: 0.85rem;">Total Pasien Dirawat</h6>
                            <h3 class="fw-bolder mb-0 text-primary" style="font-size: 1.8rem;">{{ $totalPasien }} <span class="text-muted fs-6">pasien</span></h3>
                        </div>
                        <div class="icon-circle icon-circle-primary" style="width: 45px; height: 45px; font-size: 1.2rem;">
                            <i class="mdi mdi-account-group-outline"></i>
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
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">Kepatuhan Visit per DPJP</h5>
                    <button class="btn btn-sm btn-light border bg-white fw-bold"><i class="mdi mdi-chart-line text-primary me-1"></i> Lihat Grafik</button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead style="background: #f8f9fa;">
                            <tr>
                                <th>No</th>
                                <th>DPJP</th>
                                <th>Spesialis</th>
                                <th class="text-center">Jml Pasien</th>
                                <th class="text-center">Sdh Visit</th>
                                <th class="text-center">Blm Visit</th>
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
                                <td class="text-center">{{ $stat['jumlah_pasien'] }}</td>
                                <td class="text-center">{{ $stat['sudah_visit'] }}</td>
                                <td class="text-center">{{ $stat['belum_visit'] }}</td>
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
                                <td class="text-center">{{ $totalPasien }}</td>
                                <td class="text-center">{{ $sudahVisit }}</td>
                                <td class="text-center">{{ $belumVisit }}</td>
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
    
    <!-- Bar Chart -->
    <div class="col-lg-6">
        <div class="card card-mutu h-100">
            <div class="card-body">
                <h5 class="fw-bold text-dark mb-4">Grafik Kepatuhan per DPJP</h5>
                
                <div style="height: 300px;">
                    <canvas id="kepatuhanChart"></canvas>
                </div>
                
                <div class="d-flex justify-content-center gap-4 mt-3">
                    <div class="d-flex align-items-center"><div style="width: 20px; height: 10px; background: #28a745; margin-right: 5px;"></div> <span style="font-size: 0.8rem; font-weight: 600;">≥ 95%</span></div>
                    <div class="d-flex align-items-center"><div style="width: 20px; height: 10px; background: #ffc107; margin-right: 5px;"></div> <span style="font-size: 0.8rem; font-weight: 600;">85% - < 95%</span></div>
                    <div class="d-flex align-items-center"><div style="width: 20px; height: 10px; background: #dc3545; margin-right: 5px;"></div> <span style="font-size: 0.8rem; font-weight: 600;">< 85%</span></div>
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
                    <h5 class="fw-bold text-primary mb-0">Daftar Pasien Belum Visit DPJP <span class="text-danger">({{ count($daftarBelumVisit) }} pasien)</span></h5>
                    
                    <div class="d-flex gap-2">
                        <div class="input-group input-group-sm border bg-white rounded" style="width: 250px;">
                            <input type="text" class="form-control border-0" placeholder="Cari nama pasien / No. RM">
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
                                <th>No. RM</th>
                                <th>Nama Pasien</th>
                                <th>Unit / Ruangan</th>
                                <th>DPJP</th>
                                <th class="text-center">Tanggal Masuk</th>
                                <th class="text-center">LOS</th>
                                <th class="text-center">Hari Tanpa Visit</th>
                                <th>Keterangan</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($daftarBelumVisit as $idx => $p)
                            <tr>
                                <td class="text-center border-end">{{ $idx + 1 }}</td>
                                <td class="fw-bold">{{ $p['no_rm'] }}</td>
                                <td>
                                    <div class="text-primary fw-bold">{{ $p['nama'] }}</div>
                                    <!-- Additional info could go here if needed -->
                                </td>
                                <td>{{ $p['ruangan'] }}</td>
                                <td>{{ $p['dpjp'] }}</td>
                                <td class="text-center">{{ $p['tanggal_masuk'] }}</td>
                                <td class="text-center fw-bold">{{ $p['los'] }} hari</td>
                                <td class="text-center text-danger fw-bold">{{ $p['hari_tanpa_visit'] }} hari</td>
                                <td>{{ $p['keterangan'] }}</td>
                                <td class="text-center">
                                    <button class="btn btn-light btn-sm border py-1 px-2 text-muted"><i class="mdi mdi-dots-vertical"></i></button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center py-4">Semua pasien sudah divisite oleh DPJP.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <p class="text-muted mt-3 mb-0" style="font-size: 0.8rem;"><strong>Catatan:</strong> Data kepatuhan dihitung berdasarkan ada/tidaknya catatan visite DPJP pada satu hari perawatan.</p>
            </div>
        </div>
    </div>
</div>

<!-- Chart JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@2.1.0/dist/chartjs-plugin-annotation.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Data dari Controller
    const labels = {!! json_encode($chartLabels) !!};
    const dataValues = {!! json_encode($chartData) !!};
    
    // Warna bar berdasarkan nilai kepatuhan
    const bgColors = dataValues.map(val => {
        if(val >= 95) return '#28a745';
        if(val >= 85) return '#ffc107';
        return '#dc3545';
    });

    const ctx = document.getElementById('kepatuhanChart').getContext('2d');
    
    // Plugin untuk menampilkan angka di atas bar
    const dataLabelsPlugin = {
        id: 'dataLabels',
        afterDatasetsDraw(chart, args, options) {
            const { ctx } = chart;
            chart.data.datasets.forEach((dataset, i) => {
                const meta = chart.getDatasetMeta(i);
                meta.data.forEach((bar, index) => {
                    const data = dataset.data[index];
                    ctx.fillStyle = '#333';
                    ctx.font = 'bold 12px Manrope, sans-serif';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'bottom';
                    ctx.fillText(data + '%', bar.x, bar.y - 5);
                });
            });
        }
    };

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Kepatuhan (%)',
                data: dataValues,
                backgroundColor: bgColors,
                borderWidth: 0,
                barPercentage: 0.5,
                categoryPercentage: 0.8
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
                        font: { family: 'Manrope', size: 10 },
                        maxRotation: 0,
                        autoSkip: false
                    }
                }
            },
            plugins: {
                legend: { display: false },
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
                            borderWidth: 2,
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
