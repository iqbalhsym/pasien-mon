@extends('layouts.staradmin')

@section('title', 'Kepatuhan Visit DPJP Harian')

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
                Kepatuhan Visit DPJP Harian
                <i class="mdi mdi-information-outline text-muted fs-5 ms-2" title="Persentase pasien yang telah dikunjungi (visite) oleh DPJP sesuai ketentuan"></i>
            </h2>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">Pemantauan dan koreksi riwayat visite harian DPJP rawat inap.</p>
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
    .bg-success-light { background-color: #d1e7dd; color: #0f5132; }
    .bg-danger-light { background-color: #f8d7da; color: #842029; }
</style>

<!-- FILTERS -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-mutu">
            <div class="card-body p-3">
                <form action="{{ route('mutu.kepatuhan-visit') }}" method="GET" id="filterFormVisit" class="d-flex flex-wrap gap-3 align-items-end">
                    <div style="min-width: 150px;">
                        <label class="filter-label"><i class="mdi mdi-calendar me-1"></i>Tanggal Laporan</label>
                        <input type="date" name="selected_date" class="form-control fw-bold border-primary text-primary" style="font-size: 0.9rem;" value="{{ $selectedDate }}" onchange="this.form.submit();">
                    </div>

                    <div style="min-width: 150px;">
                        <label class="filter-label"><i class="mdi mdi-calendar-range me-1"></i>Dari Tanggal</label>
                        <input type="date" name="date_from" class="form-control fw-bold" style="font-size: 0.9rem;" value="{{ $dateFrom }}" onchange="this.form.submit();">
                    </div>
                    
                    <div style="min-width: 150px;">
                        <label class="filter-label"><i class="mdi mdi-calendar-range me-1"></i>Sampai Tanggal</label>
                        <input type="date" name="date_to" class="form-control fw-bold" style="font-size: 0.9rem;" value="{{ $dateTo }}" onchange="this.form.submit();">
                    </div>

                    <div style="min-width: 180px;">
                        <label class="filter-label"><i class="mdi mdi-layers-outline me-1"></i>Lantai / Ruangan</label>
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
                        <label class="filter-label"><i class="mdi mdi-doctor me-1"></i>Spesialis</label>
                        <select name="spesialis" class="form-select fw-bold" style="font-size: 0.9rem;" onchange="this.form.submit();">
                            <option value="">Semua Spesialis</option>
                            @foreach(['Penyakit Dalam', 'Obstetri & Ginekologi', 'Bedah', 'Jantung', 'Anestesi', 'Anak'] as $sp)
                                <option value="{{ $sp }}" {{ $selectedSpesialis == $sp ? 'selected' : '' }}>{{ $sp }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ms-auto d-flex gap-2">
                        @if($selectedFloor || $selectedSpesialis || $dateFrom !== now()->startOfMonth()->toDateString() || $dateTo !== now()->toDateString() || $selectedDate !== now()->toDateString())
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

                <div class="mt-2 d-flex flex-wrap gap-2">
                    <span class="badge bg-primary text-white fw-bold px-2 py-1" style="font-size: 0.78rem;">
                        <i class="mdi mdi-calendar-check me-1"></i>Laporan: {{ date('d F Y', strtotime($selectedDate)) }}
                    </span>
                    <span class="badge bg-secondary text-white fw-bold px-2 py-1" style="font-size: 0.78rem;">
                        <i class="mdi mdi-calendar me-1"></i>Rentang Tren: {{ date('d/m/Y', strtotime($dateFrom)) }} - {{ date('d/m/Y', strtotime($dateTo)) }}
                    </span>
                    @if($selectedFloor)
                        <span class="badge bg-info text-white fw-bold px-2 py-1" style="font-size: 0.78rem;">
                            <i class="mdi mdi-layers-outline me-1"></i>Lantai: {{ is_numeric($selectedFloor) ? 'Lantai ' . $selectedFloor : $selectedFloor }}
                        </span>
                    @endif
                    @if($selectedSpesialis)
                        <span class="badge bg-success text-white fw-bold px-2 py-1" style="font-size: 0.78rem;">
                            <i class="mdi mdi-doctor me-1"></i>Spesialis: {{ $selectedSpesialis }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SCORECARDS (TANGGAL TERPILIH) -->
<div class="row mb-4 g-3">
    <!-- Main Score -->
    <div class="col-md-3">
        <div class="card card-mutu h-100">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="text-dark fw-bold mb-1" style="font-size: 0.85rem;">Kepatuhan Hari Ini</h6>
                    <h2 class="fw-bolder mb-0 text-success-dark" style="font-size: 2.8rem;">{{ $persentaseKepatuhan }}%</h2>
                    <p class="text-muted fw-bold mb-0" style="font-size: 0.85rem;">{{ $sudahVisit }} / {{ $totalPasien }} pasien</p>
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
                    <h6 class="text-dark fw-bold mb-1" style="font-size: 0.85rem;">Sudah Visit</h6>
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
                    <h6 class="text-dark fw-bold mb-1" style="font-size: 0.85rem;">Belum Visit</h6>
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
                            <h6 class="text-dark fw-bold mb-0" style="font-size: 0.8rem;">Pasien Aktif</h6>
                            <h4 class="fw-bolder mb-0 text-primary" style="font-size: 1.5rem;">{{ $totalPasien }} <span class="text-muted fs-6">pasien</span></h4>
                        </div>
                        <div class="icon-circle icon-circle-primary" style="width: 40px; height: 40px; font-size: 1.1rem;">
                            <i class="mdi mdi-account-group-outline"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 h-50">
                <div class="card card-mutu h-100 bg-white">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-dark fw-bold mb-0" style="font-size: 0.8rem;">Target Mutu</h6>
                            <h4 class="fw-bolder mb-0 text-primary" style="font-size: 1.5rem;">≥ 95%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CHARTS & TABLE DAILY SUMMARY (RENTANG TANGGAL) -->
<div class="row mb-4 g-3">
    <!-- Line Chart Tren Kepatuhan -->
    <div class="col-lg-6">
        <div class="card card-mutu h-100">
            <div class="card-body">
                <h5 class="fw-bold text-dark mb-3"><i class="mdi mdi-chart-line text-primary me-2"></i>Tren Kepatuhan Harian</h5>
                <div style="height: 310px;">
                    <canvas id="kepatuhanChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Rekap Harian -->
    <div class="col-lg-6">
        <div class="card card-mutu h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="mdi mdi-table-large text-primary me-2"></i>Rekapan Visit Harian</h5>
                </div>
                
                <div class="table-responsive" style="max-height: 310px; overflow-y: auto;">
                    <table class="table table-hover align-middle border-top">
                        <thead style="background: #f8f9fa; position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th>Tanggal</th>
                                <th class="text-center">Jml Pasien</th>
                                <th class="text-center">Sdh Visit</th>
                                <th class="text-center">Blm Visit</th>
                                <th class="text-center">Kepatuhan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dailyRecap as $day)
                            <tr style="{{ $selectedDate === $day['date'] ? 'background-color: #e3f2fd; font-weight: bold;' : '' }}">
                                <td>{{ $day['display_date'] }}</td>
                                <td class="text-center">{{ $day['total_patients'] }}</td>
                                <td class="text-center text-success">{{ $day['sudah_visit'] }}</td>
                                <td class="text-center text-danger">{{ $day['belum_visit'] }}</td>
                                <td class="text-center fw-bold {{ $day['kepatuhan'] >= 95 ? 'text-success' : ($day['kepatuhan'] >= 85 ? 'text-warning' : 'text-danger') }}">
                                    {{ $day['kepatuhan'] }}%
                                </td>
                                <td class="text-center">
                                    <a href="{{ request()->fullUrlWithQuery(['selected_date' => $day['date']]) }}" class="btn btn-primary btn-xs py-1 px-2" style="font-size: 0.73rem;">
                                        Pilih Tanggal
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SECTION HEADER DETAIL HARI TERPILIH -->
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-primary bg-white border-primary text-primary d-flex align-items-center mb-0 p-3 card-mutu shadow-sm">
            <i class="mdi mdi-calendar-clock fs-4 me-2"></i>
            <div>
                <h6 class="mb-0 fw-bold">Detail Laporan Tanggal: {{ date('d F Y', strtotime($selectedDate)) }}</h6>
                <p class="mb-0 text-muted" style="font-size: 0.8rem;">Menampilkan kepatuhan kunjungan DPJP dan daftar pasien pada tanggal ini.</p>
            </div>
        </div>
    </div>
</div>

<!-- DETAILS TABLE (DPJP & PASIEN UNTUK TANGGAL TERPILIH) -->
<div class="row g-3">
    <!-- Tabel Kepatuhan DPJP pada Hari Terpilih -->
    <div class="col-lg-5">
        <div class="card card-mutu h-100">
            <div class="card-body">
                <h5 class="fw-bold text-dark mb-3"><i class="mdi mdi-doctor text-primary me-2"></i>DPJP Hari Terpilih</h5>
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover align-middle border-top">
                        <thead>
                            <tr>
                                <th>Nama DPJP</th>
                                <th class="text-center">Pasien</th>
                                <th class="text-center">Sdh Visit</th>
                                <th class="text-center">Kepatuhan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dpjpStats as $stat)
                            <tr>
                                <td class="fw-bold text-dark" style="font-size: 0.85rem;">{{ $stat['dpjp'] }}</td>
                                <td class="text-center">{{ $stat['jumlah_pasien'] }}</td>
                                <td class="text-center text-success fw-bold">{{ $stat['sudah_visit'] }}</td>
                                <td class="text-center fw-bold {{ $stat['kepatuhan'] >= 95 ? 'text-success' : ($stat['kepatuhan'] >= 85 ? 'text-warning' : 'text-danger') }}">
                                    {{ $stat['kepatuhan'] }}%
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">Tidak ada data kunjungan untuk spesialisasi/lantai terpilih.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Pasien Hari Terpilih -->
    <div class="col-lg-7">
        <div class="card card-mutu h-100">
            <div class="card-body">
                <h5 class="fw-bold text-dark mb-3"><i class="mdi mdi-account-group text-primary me-2"></i>Daftar Pasien Hari Terpilih</h5>
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover align-middle border-top">
                        <thead>
                            <tr>
                                <th>No. RM / Pasien</th>
                                <th>Ruangan / DPJP</th>
                                <th class="text-center">LOS</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($daftarPasien as $p)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $p['no_rm'] }}</div>
                                    <div class="text-primary fw-bold" style="font-size: 0.85rem;">{{ $p['nama'] }}</div>
                                </td>
                                <td>
                                    <div class="text-muted" style="font-size: 0.8rem;"><i class="mdi mdi-home-outline me-1"></i>{{ $p['ruangan'] }}</div>
                                    <div class="fw-semibold" style="font-size: 0.8rem;"><i class="mdi mdi-doctor me-1"></i>{{ $p['dpjp'] }}</div>
                                </td>
                                <td class="text-center fw-bold">{{ $p['los'] }} Hari</td>
                                <td class="text-center">
                                    @if($p['visit_status'] === 'Sudah')
                                        <span class="badge bg-success text-white fw-bold px-2 py-1" style="font-size: 0.75rem;"><i class="mdi mdi-check-circle me-1"></i>Sudah Visit</span>
                                    @else
                                        <span class="badge bg-danger text-white fw-bold px-2 py-1" style="font-size: 0.75rem;"><i class="mdi mdi-alert-circle me-1"></i>Belum Visit</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-xs btn-toggle-visit {{ $p['visit_status'] === 'Sudah' ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                            data-id="{{ $p['id'] }}"
                                            data-date="{{ $selectedDate }}"
                                            data-status="{{ $p['visit_status'] === 'Sudah' ? 0 : 1 }}"
                                            style="font-size: 0.73rem; font-weight: bold; width: 110px; border-radius: 4px;">
                                        <i class="mdi {{ $p['visit_status'] === 'Sudah' ? 'mdi-close-circle' : 'mdi-check-circle' }} me-1"></i>
                                        {{ $p['visit_status'] === 'Sudah' ? 'Batal Visit' : 'Tandai Visit' }}
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">Semua pasien sudah divisite oleh DPJP pada tanggal ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart JS & Toggle Visit AJAX Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // --- 1. CONFIG LINE CHART ---
    const labels = {!! json_encode($chartLabels) !!};
    const dataValues = {!! json_encode($chartData) !!};

    const ctx = document.getElementById('kepatuhanChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Kepatuhan (%)',
                data: dataValues,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.05)',
                borderWidth: 3,
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#0d6efd',
                pointRadius: 4,
                pointHoverRadius: 6
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
                    grid: { color: '#f0f0f0' }
                },
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { family: 'Manrope', size: 10 },
                        maxRotation: 0
                    }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });

    // --- 2. AJAX TOGGLE VISIT ---
    document.querySelectorAll('.btn-toggle-visit').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const patientId = btn.getAttribute('data-id');
            const date = btn.getAttribute('data-date');
            const status = btn.getAttribute('data-status');
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>...';
            
            fetch("{{ route('mutu.kepatuhan-visit.toggle') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    equipment_id: patientId,
                    date: date,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Gagal memperbarui status visit.');
                    btn.disabled = false;
                    btn.innerHTML = status == 1 ? '<i class="mdi mdi-check-circle me-1"></i>Tandai Visit' : '<i class="mdi mdi-close-circle me-1"></i>Batal Visit';
                }
            })
            .catch(error => {
                alert('Terjadi kesalahan koneksi.');
                btn.disabled = false;
                btn.innerHTML = status == 1 ? '<i class="mdi mdi-check-circle me-1"></i>Tandai Visit' : '<i class="mdi mdi-close-circle me-1"></i>Batal Visit';
            });
        });
    });
});
</script>

@stop
