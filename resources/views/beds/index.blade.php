@extends('layouts.staradmin')

@section('title', 'Monitoring Bed & Kamar')

@section('content_header')
<div class="row align-items-center mb-4">
    <div class="col-sm-8">
        <h2 class="h2 text-dark font-weight-bold mb-1">
            <i class="mdi mdi-bed text-primary me-2"></i> Monitoring Bed & Kamar
        </h2>
        <p class="text-muted mb-0" style="font-size: 1.05rem;">Status occupancy, ketersediaan tempat tidur, dan penempatan pasien secara real-time.</p>
    </div>
    <div class="col-sm-4 text-sm-end mt-3 mt-sm-0">
        @if(auth()->user()->role !== 'viewer')
            <button id="syncBtn" class="btn btn-primary fw-bold px-4 py-2 shadow-sm d-inline-flex align-items-center" style="font-size: 1rem;">
                <i class="mdi mdi-sync me-2 fs-5"></i> <span>Sinkronisasi Real-Time</span>
            </button>
        @endif
    </div>
</div>
@stop

@section('content')
<!-- Section 1: Dashboard Statistika -->
<div class="row mb-4">
    <!-- Occupancy Rate -->
    <div class="col-xl-3 col-sm-6 grid-margin stretch-card mb-3">
        <div class="card bg-gradient-primary text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #1F3BB3 0%, #0d1e6d 100%); border-radius: 12px;">
            <div class="card-body py-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-white opacity-75 mb-1 fw-bold" style="font-size: 0.9rem;">Occupancy Rate</p>
                        <h2 class="display-4 fw-bold text-white mb-0">{{ $occupancyRate }}%</h2>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3">
                        <i class="mdi mdi-chart-donut text-white fs-3"></i>
                    </div>
                </div>
                <div class="progress progress-md mt-3 bg-white bg-opacity-25" style="height: 6px; border-radius: 3px;">
                    <div class="progress-bar bg-white" role="progressbar" style="width: {{ $occupancyRate }}%" aria-valuenow="{{ $occupancyRate }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terisi -->
    <div class="col-xl-2 col-sm-4 grid-margin stretch-card mb-3">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 5px solid #dc3545 !important;">
            <div class="card-body py-3">
                <p class="text-muted mb-1 fw-bold" style="font-size: 0.85rem;">BED TERISI</p>
                <div class="d-flex align-items-center justify-content-between">
                    <h2 class="fw-bold text-danger mb-0">{{ $occupiedBeds }}</h2>
                    <i class="mdi mdi-bed text-danger fs-3"></i>
                </div>
                <small class="text-muted d-block mt-2">Sedang digunakan pasien</small>
            </div>
        </div>
    </div>

    <!-- Kosong -->
    <div class="col-xl-2 col-sm-4 grid-margin stretch-card mb-3">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 5px solid #198754 !important;">
            <div class="card-body py-3">
                <p class="text-muted mb-1 fw-bold" style="font-size: 0.85rem;">BED KOSONG</p>
                <div class="d-flex align-items-center justify-content-between">
                    <h2 class="fw-bold text-success mb-0">{{ $vacantBeds }}</h2>
                    <i class="mdi mdi-bed-empty text-success fs-3"></i>
                </div>
                <small class="text-muted d-block mt-2">Siap untuk pasien baru</small>
            </div>
        </div>
    </div>

    <!-- Cleaning -->
    <div class="col-xl-2 col-sm-4 grid-margin stretch-card mb-3">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 5px solid #fd7e14 !important;">
            <div class="card-body py-3">
                <p class="text-muted mb-1 fw-bold" style="font-size: 0.85rem;">PEMBERSIHAN</p>
                <div class="d-flex align-items-center justify-content-between">
                    <h2 class="fw-bold text-warning mb-0" style="color: #fd7e14 !important;">{{ $cleaningBeds }}</h2>
                    <i class="mdi mdi-vacuum text-warning fs-3" style="color: #fd7e14 !important;"></i>
                </div>
                <small class="text-muted d-block mt-2">Proses disinfeksi / cleaning</small>
            </div>
        </div>
    </div>

    <!-- Total Active -->
    <div class="col-xl-3 col-sm-6 grid-margin stretch-card mb-3">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 5px solid #6c757d !important; background-color: #f8f9fa;">
            <div class="card-body py-3">
                <p class="text-muted mb-1 fw-bold" style="font-size: 0.85rem;">TOTAL TEMPAT TIDUR</p>
                <div class="d-flex align-items-center justify-content-between">
                    <h2 class="fw-bold text-dark mb-0">{{ $totalBeds }}</h2>
                    <i class="mdi mdi-hospital-building text-secondary fs-3"></i>
                </div>
                <small class="text-muted d-block mt-2">
                    Aktif: <b>{{ $totalBeds - $inactiveBeds }}</b> | Non-Aktif: <b>{{ $inactiveBeds }}</b>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Section 2: Tab Navigasi Lantai -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-body p-2">
                <div class="d-flex flex-wrap gap-2 justify-content-start">
                    @foreach($floors as $fl)
                        @php
                            $flName = $fl->name;
                            $displayFl = is_numeric($flName) ? 'Lantai ' . $flName : $flName;
                            $isActiveTab = $selectedFloorName == $flName;
                        @endphp
                        <a href="{{ route('beds.index', ['floor' => $flName]) }}" 
                           class="btn {{ $isActiveTab ? 'btn-primary' : 'btn-outline-secondary' }} px-4 py-2 fw-bold d-flex align-items-center"
                           style="border-radius: 8px; font-size: 0.92rem;">
                            <i class="mdi mdi-layers-outline me-2"></i> {{ $displayFl }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section 3: Visual Layout Kamar dan Bed -->
@if($selectedFloor)
    <h3 class="h4 text-dark font-weight-bold mb-3 d-flex align-items-center">
        <i class="mdi mdi-door-open text-primary me-2"></i> Daftar Ruang Rawat & Bed: 
        <span class="text-primary ms-1 fw-bold">{{ is_numeric($selectedFloorName) ? 'Lantai ' . $selectedFloorName : $selectedFloorName }}</span>
    </h3>

    @forelse($wings as $wing)
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
            <!-- Header Wing -->
            <div class="card-header bg-dark text-white px-4 py-3 d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold text-white fs-4 d-flex align-items-center">
                    <i class="mdi mdi-home-variant text-warning me-2 fs-4"></i> BAGIAN / WING: {{ $wing->name }}
                </h4>
                <span class="badge bg-light text-dark fw-bold px-3 py-2">
                    {{ $wing->rooms->count() }} Ruangan
                </span>
            </div>

            <div class="card-body p-4 bg-light bg-opacity-25">
                <div class="row">
                    @forelse($wing->rooms as $room)
                        <!-- Room Card -->
                        <div class="col-lg-6 col-xl-4 mb-4">
                            <div class="card border border-light h-100 shadow-sm" style="border-radius: 10px; background-color: #ffffff;">
                                <!-- Room Title Bar -->
                                <div class="card-header border-bottom-0 bg-light bg-opacity-75 px-3 py-2.5 d-flex justify-content-between align-items-center" style="border-top-left-radius: 10px; border-top-right-radius: 10px;">
                                    <div>
                                        <h5 class="fw-bold text-dark mb-0 fs-5">Kamar {{ $room->name }}</h5>
                                        <span class="badge bg-secondary text-white fs-6 mt-1">{{ $room->class ?? 'Tanpa Kelas' }}</span>
                                    </div>
                                    <span class="badge bg-dark text-white fw-bold px-2.5 py-1">
                                        {{ $room->beds->where('status', 'terisi')->count() }} / {{ $room->beds->count() }} Bed
                                    </span>
                                </div>

                                <!-- Room Beds Layout -->
                                <div class="card-body p-3">
                                    <div class="d-flex flex-column gap-2">
                                        @forelse($room->beds as $bed)
                                            @php
                                                $status = $bed->status;
                                                $isActive = $bed->is_active;
                                                $patient = $bed->equipment;

                                                // Color styles based on bed status
                                                $cardBg = 'bg-light border';
                                                $badgeClass = 'bg-secondary';
                                                $badgeLabel = 'OFFLINE';

                                                if ($isActive) {
                                                    if ($status == 'terisi') {
                                                        $cardBg = 'border-danger bg-danger bg-opacity-10';
                                                        $badgeClass = 'bg-danger';
                                                        $badgeLabel = 'TERISI';
                                                    } elseif ($status == 'kosong') {
                                                        $cardBg = 'border-success bg-success bg-opacity-10';
                                                        $badgeClass = 'bg-success';
                                                        $badgeLabel = 'KOSONG';
                                                    } elseif ($status == 'cleaning') {
                                                        $cardBg = 'border-warning bg-warning bg-opacity-10';
                                                        $badgeClass = 'bg-warning text-dark';
                                                        $badgeLabel = 'CLEANING';
                                                    }
                                                }
                                            @endphp

                                            <!-- Bed Strip Item -->
                                            <div class="d-flex align-items-center justify-content-between p-2.5 rounded border {{ $cardBg }}" style="transition: all 0.2s ease;">
                                                <div class="d-flex align-items-center gap-2">
                                                    @if(!$isActive)
                                                        <i class="mdi mdi-bed-cancel text-muted fs-4"></i>
                                                    @elseif($status == 'terisi')
                                                        <i class="mdi mdi-bed text-danger fs-4"></i>
                                                    @elseif($status == 'kosong')
                                                        <i class="mdi mdi-bed-empty text-success fs-4"></i>
                                                    @elseif($status == 'cleaning')
                                                        <i class="mdi mdi-vacuum text-warning fs-4 mdi-spin"></i>
                                                    @endif

                                                    <div>
                                                        <div class="fw-bold text-dark" style="font-size: 0.92rem;">
                                                            Bed {{ $bed->bed_number }}
                                                        </div>

                                                        <!-- Patient Info if occupied -->
                                                        @if($status == 'terisi' && $patient)
                                                            <a href="{{ route('maintenances.patient_detail', $patient->serial_number) }}" class="text-decoration-none">
                                                                <div class="text-primary mt-1 hover-underline" style="font-size: 0.88rem; font-weight: 700;">
                                                                    {{ $patient->merk }}
                                                                </div>
                                                            </a>
                                                            <div class="text-muted" style="font-size: 0.8rem;">
                                                                No. RM: <b>{{ $patient->serial_number }}</b>
                                                            </div>
                                                            <div class="text-muted text-truncate" style="font-size: 0.8rem; max-width: 180px;" title="{{ $patient->type }}">
                                                                Diag: {{ $patient->type }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Status Badge & Action -->
                                                <div class="text-end">
                                                    <span class="badge {{ $badgeClass }} px-2 py-1 mb-1 fw-bold" style="font-size: 0.72rem;">
                                                        {{ $badgeLabel }}
                                                    </span>
                                                    
                                                    @if($status == 'terisi' && $patient)
                                                        <a href="{{ route('maintenances.patient_detail', $patient->serial_number) }}" 
                                                           class="btn btn-info btn-sm text-white py-1 px-2.5 d-block mt-1 fw-bold shadow-sm"
                                                           style="font-size: 0.75rem;">
                                                            <i class="mdi mdi-account-card-details me-1"></i> Detail
                                                        </a>
                                                        <a href="{{ route('maintenances.history', $patient->serial_number) }}" 
                                                           class="btn btn-dark btn-sm text-white py-1 px-2.5 d-block mt-1 fw-bold shadow-sm"
                                                           style="font-size: 0.75rem;">
                                                            <i class="mdi mdi-file-document-outline me-1"></i> Riwayat
                                                        </a>
                                                    @elseif($status == 'kosong' && auth()->user()->role !== 'viewer')
                                                        <!-- Short register link that autofills room & floor -->
                                                        <a href="{{ route('maintenances.index', ['register' => 1, 'lantai' => $selectedFloorName, 'wing' => $wing->name, 'room' => $room->name]) }}" 
                                                           class="btn btn-outline-success btn-sm py-1 px-2 d-block mt-1 fw-bold"
                                                           style="font-size: 0.75rem;">
                                                            <i class="mdi mdi-account-plus me-1"></i> Daftar
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-center py-3 text-muted">
                                                Tidak ada bed terdaftar.
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center py-4">
                            <h5 class="text-muted">Tidak ada kamar di wing ini.</h5>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    @empty
        <div class="card border-0 shadow-sm p-5 text-center">
            <i class="mdi mdi-alert text-muted" style="font-size: 4rem;"></i>
            <h4 class="mt-3 text-dark fw-bold">Belum Ada Data Bagian/Wing di Lantai Ini</h4>
        </div>
    @endforelse
@else
    <div class="card border-0 shadow-sm p-5 text-center">
        <i class="mdi mdi-layers-off text-muted" style="font-size: 4rem;"></i>
        <h4 class="mt-3 text-dark fw-bold">Silakan Pilih Lantai Untuk Memulai Monitoring</h4>
    </div>
@endif

<!-- Script AJAX untuk sinkronisasi -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const syncBtn = document.getElementById('syncBtn');
        if (syncBtn) {
            syncBtn.addEventListener('click', function () {
                const btn = this;
                const icon = btn.querySelector('i');
                const label = btn.querySelector('span');

                // Set loading state
                btn.disabled = true;
                icon.classList.add('mdi-spin');
                label.innerText = 'Mensinkronkan...';

                fetch("{{ route('beds.sync') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Server returned error status');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.reload();
                    } else {
                        alert('Gagal: ' + data.message);
                        resetBtn();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal terhubung ke server untuk sinkronisasi.');
                    resetBtn();
                });

                function resetBtn() {
                    btn.disabled = false;
                    icon.classList.remove('mdi-spin');
                    label.innerText = 'Sinkronisasi Real-Time';
                }
            });
        }
    });
</script>
@stop
