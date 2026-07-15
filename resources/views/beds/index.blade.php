@extends('layouts.staradmin')

@section('title', 'Monitoring Bed & Kamar')

@section('content_header')
<style>
    .floor-card {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        background: #ffffff;
    }
    .floor-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08) !important;
        border-color: #1F3BB3 !important;
    }
    .active-floor-card {
        background-color: #f8f9ff !important;
        box-shadow: 0 4px 15px rgba(31, 59, 179, 0.08) !important;
    }
    .live-dot-pulse {
        width: 10px;
        height: 10px;
        background-color: #198754;
        border-radius: 50%;
        position: relative;
        display: inline-block;
    }
    .live-dot-pulse::after {
        content: '';
        width: 10px;
        height: 10px;
        background-color: #198754;
        border-radius: 50%;
        position: absolute;
        top: 0;
        left: 0;
        animation: livePulse 1.8s infinite ease-in-out;
    }
    @keyframes livePulse {
        0% {
            transform: scale(1);
            opacity: 1;
        }
        100% {
            transform: scale(2.8);
            opacity: 0;
        }
    }
    .live-dot-paused {
        width: 10px;
        height: 10px;
        background-color: #6c757d;
        border-radius: 50%;
        display: inline-block;
    }
    .live-dot-paused::after {
        display: none !important;
    }
    .cursor-pointer {
        cursor: pointer;
    }
    .hover-underline:hover {
        text-decoration: underline !important;
    }
    .border-orange {
        border-color: #fd7e14 !important;
    }
    .bg-orange {
        background-color: #fd7e14 !important;
    }
    .bg-orange-opacity-10 {
        background-color: rgba(253, 126, 20, 0.1) !important;
    }
    .text-orange {
        color: #fd7e14 !important;
    }
</style>

<div class="row align-items-center mb-4">
    <div class="col-sm-6 col-md-7">
        <h2 class="h2 text-dark font-weight-bold mb-1">
            <i class="mdi mdi-bed text-primary me-2"></i> Monitoring Bed & Kamar
        </h2>
        <p class="text-muted mb-0" style="font-size: 1.05rem;">Status occupancy, ketersediaan tempat tidur, dan penempatan pasien secara real-time.</p>
    </div>
    <div class="col-sm-6 col-md-5 text-sm-end mt-3 mt-sm-0 d-flex align-items-center justify-content-sm-end gap-2 flex-wrap">
        <!-- Live Indicator and Auto Refresh Toggle -->
        <div class="d-flex align-items-center bg-white px-3 py-2 rounded shadow-sm border border-light" style="height: 42px;">
            <div id="liveIndicator" class="live-dot-pulse me-2"></div>
            <span id="liveText" class="text-dark fw-bold me-3" style="font-size: 0.85rem; letter-spacing: 0.5px;">LIVE (20s)</span>
            <div class="form-check form-switch mb-0 p-0 d-flex align-items-center">
                <input class="form-check-input ms-0 cursor-pointer" type="checkbox" role="switch" id="autoRefreshSwitch" checked style="width: 2.2em; height: 1.1em;">
            </div>
        </div>

        @if(auth()->user()->role !== 'viewer')
            <button id="syncBtn" class="btn btn-primary fw-bold px-4 py-2 shadow-sm d-inline-flex align-items-center" style="font-size: 1rem; height: 42px; border-radius: 8px;">
                <i class="mdi mdi-sync me-2 fs-5"></i> <span>Sinkronisasi</span>
            </button>
        @endif
    </div>
</div>
@stop

@section('content')
<!-- Section 1: Dashboard Statistika -->
<div id="global-stats-container" class="row mb-4">
    <!-- Occupancy Rate -->
    <div class="col-xl-2 col-sm-4 grid-margin stretch-card mb-3">
        <div class="card bg-gradient-primary text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #1F3BB3 0%, #0d1e6d 100%); border-radius: 12px;">
            <div class="card-body py-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-white opacity-75 mb-1 fw-bold" style="font-size: 0.9rem;">Occupancy Rate</p>
                        <h2 class="display-4 fw-bold text-white mb-0" style="font-size: 1.8rem;">{{ $occupancyRate }}%</h2>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-2">
                        <i class="mdi mdi-chart-donut text-white fs-4"></i>
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
                <p class="text-muted mb-1 fw-bold" style="font-size: 0.85rem;">Occupied</p>
                <div class="d-flex align-items-center justify-content-between">
                    <h2 class="fw-bold text-danger mb-0">{{ $occupiedBeds }}</h2>
                    <i class="mdi mdi-bed text-danger fs-3"></i>
                </div>
                <small class="text-muted d-block mt-2">Sedang digunakan pasien</small>
            </div>
        </div>
    </div>

    <!-- Terbooking -->
    <div class="col-xl-2 col-sm-4 grid-margin stretch-card mb-3">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 5px solid #ffc107 !important;">
            <div class="card-body py-3">
                <p class="text-muted mb-1 fw-bold" style="font-size: 0.85rem;">Booked</p>
                <div class="d-flex align-items-center justify-content-between">
                    <h2 class="fw-bold text-warning mb-0">{{ $bookedBeds }}</h2>
                    <i class="mdi mdi-calendar-check text-warning fs-3"></i>
                </div>
                <small class="text-muted d-block mt-2">Dipesan / terbooking dari API</small>
            </div>
        </div>
    </div>

    <!-- Kosong -->
    <div class="col-xl-2 col-sm-4 grid-margin stretch-card mb-3">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 5px solid #198754 !important;">
            <div class="card-body py-3">
                <p class="text-muted mb-1 fw-bold" style="font-size: 0.85rem;">Available</p>
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
                <p class="text-muted mb-1 fw-bold" style="font-size: 0.85rem;">Cleaning</p>
                <div class="d-flex align-items-center justify-content-between">
                    <h2 class="fw-bold text-warning mb-0" style="color: #fd7e14 !important;">{{ $cleaningBeds }}</h2>
                    <i class="mdi mdi-vacuum text-warning fs-3" style="color: #fd7e14 !important;"></i>
                </div>
                <small class="text-muted d-block mt-2">Proses disinfeksi / cleaning</small>
            </div>
        </div>
    </div>

    <!-- Total Active -->
    <div class="col-xl-2 col-sm-4 grid-margin stretch-card mb-3">
        <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 5px solid #6c757d !important; background-color: #f8f9fa;">
            <div class="card-body py-3">
                <p class="text-muted mb-1 fw-bold" style="font-size: 0.85rem;">Total Bed</p>
                <div class="d-flex align-items-center justify-content-between">
                    <h2 class="fw-bold text-dark mb-0">{{ $totalBeds }}</h2>
                    <i class="mdi mdi-hospital-building text-secondary fs-3"></i>
                </div>
                <small class="text-muted d-block mt-2">
                    Bed Aktif: <b>{{ $totalBeds }}</b> | Non: <b>{{ $inactiveBeds }}</b>
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Section 2: Dashboard Per Lantai (Floor Dashboards) -->
<div class="row mb-1">
    <div class="col-12">
        <h4 class="h4 text-dark font-weight-bold mb-3 d-flex align-items-center">
            <i class="mdi mdi-layers-outline text-primary me-2"></i> Ringkasan Dashboard Per Lantai
        </h4>
    </div>
</div>
<div id="floor-dashboards-container" class="row mb-4">
    @foreach($floors as $fl)
        @php
            $flName = $fl->name;
            $displayFl = is_numeric($flName) ? 'Lantai ' . $flName : $flName;
            $isActiveTab = $selectedFloorName == $flName;
        @endphp
        <div class="col-xl-3 col-md-4 col-sm-6 mb-3">
            <a href="{{ route('beds.index', ['floor' => $flName]) }}" 
               class="floor-card-link text-decoration-none" 
               data-floor="{{ $flName }}">
                <div class="card h-100 border-0 shadow-sm floor-card {{ $isActiveTab ? 'active-floor-card' : '' }}" 
                     style="border-radius: 12px; transition: all 0.25s ease; position: relative; overflow: hidden; border: 2px solid {{ $isActiveTab ? '#1F3BB3' : 'transparent' }} !important;">
                    
                    @if($isActiveTab)
                        <div class="active-badge shadow-sm" style="position: absolute; top: 0; right: 0; background-color: #1F3BB3; color: white; padding: 2px 10px; font-size: 0.65rem; font-weight: 800; border-bottom-left-radius: 8px; letter-spacing: 0.5px;">
                            TERPILIH
                        </div>
                    @endif
                    
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="fw-bold mb-0 text-dark {{ $isActiveTab ? 'text-primary' : '' }}" style="font-size: 1.05rem;">
                                    {{ $displayFl }}
                                </h5>
                                <span class="text-muted" style="font-size: 0.78rem;">Status occupancy</span>
                            </div>
                            <div class="p-2 rounded {{ $isActiveTab ? 'bg-primary bg-opacity-10 text-primary' : 'bg-light text-secondary' }}">
                                <i class="mdi mdi-layers-outline fs-5"></i>
                            </div>
                        </div>

                        <!-- Floor Occupancy Rate -->
                        <div class="d-flex align-items-baseline mb-1">
                            <h3 class="fw-bold mb-0 text-dark" style="font-size: 1.5rem;">{{ $fl->occupancy_rate }}%</h3>
                            <span class="text-muted ms-2" style="font-size: 0.78rem;">Occupancy</span>
                        </div>
                        <div class="progress progress-sm mb-3 bg-light" style="height: 6px; border-radius: 3px;">
                            <div class="progress-bar {{ $isActiveTab ? 'bg-primary' : 'bg-secondary bg-opacity-50' }}" 
                                 role="progressbar" 
                                 style="width: {{ $fl->occupancy_rate }}%" 
                                 aria-valuenow="{{ $fl->occupancy_rate }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100"></div>
                        </div>

                        <!-- Floor Stats Grid -->
                        <div class="row g-1 text-center pt-2 border-top border-light">
                            <div class="col">
                                <div class="p-1">
                                    <div class="fw-bold text-danger mb-0" style="font-size: 0.85rem;">{{ $fl->occupied_beds }}</div>
                                    <div class="text-muted" style="font-size: 0.58rem; font-weight: 700; letter-spacing: 0.1px;">OCCUPIED</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="p-1">
                                    <div class="fw-bold text-warning mb-0" style="font-size: 0.85rem;">{{ $fl->booked_beds }}</div>
                                    <div class="text-muted" style="font-size: 0.58rem; font-weight: 700; letter-spacing: 0.1px;">BOOKED</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="p-1">
                                    <div class="fw-bold text-success mb-0" style="font-size: 0.85rem;">{{ $fl->vacant_beds }}</div>
                                    <div class="text-muted" style="font-size: 0.58rem; font-weight: 700; letter-spacing: 0.1px;">AVAILABLE</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="p-1">
                                    <div class="fw-bold text-orange mb-0" style="font-size: 0.85rem;">{{ $fl->cleaning_beds }}</div>
                                    <div class="text-muted" style="font-size: 0.58rem; font-weight: 700; letter-spacing: 0.1px;">CLEANING</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="p-1">
                                    <div class="fw-bold text-secondary mb-0" style="font-size: 0.85rem;">{{ $fl->total_active_beds }}</div>
                                    <div class="text-muted" style="font-size: 0.58rem; font-weight: 700; letter-spacing: 0.1px;">TOTAL BED</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

<!-- Section 3: Visual Layout Kamar dan Bed -->
<div id="dashboard-layout-container" style="transition: opacity 0.3s ease;">
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
                                        {{ $room->beds->where('status', 'terisi')->where('is_active', true)->count() }} / {{ $room->beds->where('is_active', true)->count() }} Bed
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
                                                        $badgeLabel = 'OCCUPIED';
                                                    } elseif ($status == 'kosong') {
                                                        $cardBg = 'border-success bg-success bg-opacity-10';
                                                        $badgeClass = 'bg-success';
                                                        $badgeLabel = 'AVAILABLE';
                                                    } elseif ($status == 'cleaning') {
                                                        $cardBg = 'border-orange bg-orange-opacity-10';
                                                        $badgeClass = 'bg-orange text-white';
                                                        $badgeLabel = 'CLEANING';
                                                    } elseif ($status == 'booking') {
                                                        $cardBg = 'border-warning bg-warning bg-opacity-10';
                                                        $badgeClass = 'bg-warning text-dark';
                                                        $badgeLabel = 'BOOKED';
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
                                                        <i class="mdi mdi-vacuum text-orange fs-4 mdi-spin"></i>
                                                    @elseif($status == 'booking')
                                                        <i class="mdi mdi-calendar-clock text-warning fs-4"></i>
                                                    @endif

                                                    <div>
                                                        <div class="fw-bold text-dark" style="font-size: 0.92rem;">
                                                            Bed {{ $bed->bed_number }}
                                                        </div>

                                                        <!-- Patient Info if occupied or booked with patient -->
                                                        @if(($status == 'terisi' || $status == 'booking') && $patient)
                                                            <a href="{{ route('maintenances.patient_detail', $patient->serial_number) }}" class="text-decoration-none">
                                                                <div class="text-primary mt-1 hover-underline" style="font-size: 0.88rem; font-weight: 700;">
                                                                    {{ $patient->merk }}
                                                                </div>
                                                            </a>
                                                            <div class="text-muted" style="font-size: 0.8rem;">
                                                                No. RM: <b>{{ $patient->serial_number }}</b>
                                                                @if($patient->guarantor)
                                                                    <span class="badge bg-light text-dark border ms-1 py-0.5 px-1.5 fw-bold" style="font-size: 0.7rem;">{{ $patient->guarantor }}</span>
                                                                @endif
                                                            </div>
                                                            <div class="text-muted text-truncate" style="font-size: 0.8rem; max-width: 180px;" title="{{ $patient->type }}">
                                                                Diag: {{ $patient->type }}
                                                            </div>
                                                            @if($patient->ews)
                                                                @php
                                                                    $ewsLower = strtolower($patient->ews);
                                                                    $ewsBadgeClass = 'bg-secondary text-white';
                                                                    if (str_contains($ewsLower, 'hijau')) {
                                                                        $ewsBadgeClass = 'bg-success text-white';
                                                                    } elseif (str_contains($ewsLower, 'kuning')) {
                                                                        $ewsBadgeClass = 'bg-warning text-dark';
                                                                    } elseif (str_contains($ewsLower, 'orange') || str_contains($ewsLower, 'oranye')) {
                                                                        $ewsBadgeClass = 'bg-orange text-white';
                                                                    } elseif (str_contains($ewsLower, 'merah')) {
                                                                        $ewsBadgeClass = 'bg-danger text-white';
                                                                    } elseif (str_contains($ewsLower, 'dnr')) {
                                                                        $ewsBadgeClass = 'bg-dark text-white';
                                                                    }
                                                                @endphp
                                                                <div class="mt-1" style="font-size: 0.78rem;">
                                                                    EWS: <span class="badge {{ $ewsBadgeClass }} py-0.5 px-1.5 fw-bold" style="font-size: 0.72rem;">{{ $patient->ews }}</span>
                                                                </div>
                                                            @endif

                                                            <!-- Ners Shift Assignment Display -->
                                                            <div class="mt-2 pt-1" style="font-size: 0.76rem; border-top: 1px dashed #dee2e6 !important;">
                                                                <div class="d-flex flex-column gap-0.5">
                                                                    <div class="text-muted text-truncate" style="max-width: 190px;" title="Ners Pagi: {{ $patient->ners_pagi ?: '-' }}">
                                                                        <span class="text-primary fw-bold">🌅 Pagi:</span> {{ $patient->ners_pagi ?: '-' }}
                                                                    </div>
                                                                    <div class="text-muted text-truncate" style="max-width: 190px;" title="Ners Siang: {{ $patient->ners_siang ?: '-' }}">
                                                                        <span class="text-warning fw-bold">☀️ Siang:</span> {{ $patient->ners_siang ?: '-' }}
                                                                    </div>
                                                                    <div class="text-muted text-truncate" style="max-width: 190px;" title="Ners Malam: {{ $patient->ners_malam ?: '-' }}">
                                                                        <span class="text-info fw-bold">🌙 Malam:</span> {{ $patient->ners_malam ?: '-' }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif

                                                        @if(!empty($bed->future_patients) && is_array($bed->future_patients) && count($bed->future_patients) > 0)
                                                            <div class="mt-2 pt-2 border-top border-secondary border-opacity-10" style="font-size: 0.8rem;">
                                                                <div class="text-warning fw-bold mb-1.5" style="font-size: 0.72rem; letter-spacing: 0.3px;">
                                                                    <i class="mdi mdi-calendar-check text-warning me-0.5"></i> BOOKING INCOMING:
                                                                </div>
                                                                @foreach($bed->future_patients as $future)
                                                                    <div class="p-1.5 rounded bg-warning bg-opacity-10 border border-warning border-opacity-20 mb-1" style="line-height: 1.3;">
                                                                        <div class="fw-bold text-dark text-uppercase" style="font-size: 0.82rem;">
                                                                            {{ $future['name'] ?? '-' }}
                                                                        </div>
                                                                        <div class="text-muted" style="font-size: 0.75rem;">
                                                                            RM: <b>{{ $future['no_rm'] ?? '-' }}</b>
                                                                            @if(!empty($future['asal_booking']))
                                                                                <span class="badge bg-light text-dark border ms-1 fw-bold" style="font-size: 0.65rem;">Asal: {{ $future['asal_booking'] }}</span>
                                                                            @endif
                                                                        </div>
                                                                        @if(!empty($future['book_date']))
                                                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                                                Tgl: {{ date('d-m-Y', strtotime($future['book_date'])) }}
                                                                            </div>
                                                                        @endif
                                                                        @if(!empty($future['dpjp']))
                                                                            <div class="text-muted text-truncate" style="font-size: 0.75rem; max-width: 170px;" title="DPJP: {{ $future['dpjp'] }}">
                                                                                Dr: {{ $future['dpjp'] }}
                                                                            </div>
                                                                        @endif
                                                                        @if(!empty($future['note']))
                                                                            <div class="text-muted small text-truncate" style="font-size: 0.72rem; max-width: 170px; font-style: italic;" title="Catatan: {{ $future['note'] }}">
                                                                                "{{ $future['note'] }}"
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Status Badge & Action -->
                                                <div class="text-end">
                                                    <span class="badge {{ $badgeClass }} px-2 py-1 mb-1 fw-bold" style="font-size: 0.72rem;">
                                                        {{ $badgeLabel }}
                                                    </span>
                                                    
                                                    @if(($status == 'terisi' || $status == 'booking') && $patient)
                                                        <a href="{{ route('maintenances.patient_detail', $patient->serial_number) }}" 
                                                           class="btn btn-info btn-sm text-white py-1 px-2.5 d-block mt-1 fw-bold shadow-sm"
                                                           style="font-size: 0.75rem;">
                                                            <i class="mdi mdi-account-card-details me-1"></i> Detail
                                                        </a>
                                                        @if(auth()->user()->role !== 'viewer')
                                                            <button type="button" 
                                                                    class="btn btn-primary btn-sm text-white py-1 px-2.5 d-block w-100 mt-1 fw-bold shadow-sm edit-ners-btn" 
                                                                    data-id="{{ $patient->id }}"
                                                                    data-bed="{{ $bed->bed_number }}"
                                                                    data-pagi="{{ $patient->ners_pagi }}"
                                                                    data-siang="{{ $patient->ners_siang }}"
                                                                    data-malam="{{ $patient->ners_malam }}"
                                                                    style="font-size: 0.75rem;">
                                                                <i class="mdi mdi-account-star me-1"></i> Ners
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn btn-outline-warning btn-sm text-dark py-1 px-2.5 d-block w-100 mt-1 fw-bold shadow-sm edit-ews-btn" 
                                                                    data-id="{{ $patient->id }}"
                                                                    data-bed="{{ $bed->bed_number }}"
                                                                    data-ews="{{ $patient->ews }}"
                                                                    style="font-size: 0.75rem;">
                                                                <i class="mdi mdi-heart-pulse text-warning me-1"></i> EWS
                                                            </button>
                                                        @endif
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
</div>

<!-- Modal Update Ners -->
<div class="modal fade" id="nersModal" tabindex="-1" aria-labelledby="nersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header bg-primary text-white" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <h5 class="modal-title fw-bold text-white" id="nersModalLabel"><i class="mdi mdi-account-star me-1"></i> Update Ners - Bed <span id="modalBedNum"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="nersForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <input type="hidden" id="modalEquipmentId" name="equipment_id">
                    
                    <div class="mb-3">
                        <label for="inputNersPagi" class="form-label fw-bold text-dark"><i class="mdi mdi-white-balance-sunny text-primary me-1"></i> Ners Pagi</label>
                        <input type="text" class="form-control text-dark fw-bold" id="inputNersPagi" name="ners_pagi" placeholder="Pilih atau ketik nama Ners Pagi" list="nurses_list" autocomplete="off">
                    </div>
                    
                    <div class="mb-3">
                        <label for="inputNersSiang" class="form-label fw-bold text-dark"><i class="mdi mdi-weather-sunset text-warning me-1"></i> Ners Siang</label>
                        <input type="text" class="form-control text-dark fw-bold" id="inputNersSiang" name="ners_siang" placeholder="Pilih atau ketik nama Ners Siang" list="nurses_list" autocomplete="off">
                    </div>
                    
                    <div class="mb-3">
                        <label for="inputNersMalam" class="form-label fw-bold text-dark"><i class="mdi mdi-weather-night text-info me-1"></i> Ners Malam</label>
                        <input type="text" class="form-control text-dark fw-bold" id="inputNersMalam" name="ners_malam" placeholder="Pilih atau ketik nama Ners Malam" list="nurses_list" autocomplete="off">
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                    <button type="button" class="btn btn-secondary fw-bold px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="saveNersBtn" class="btn btn-primary fw-bold px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<datalist id="nurses_list">
    @foreach($activeNurses as $nurse)
        <option value="{{ $nurse->name }}"></option>
    @endforeach
</datalist>

<!-- Modal Update EWS -->
<div class="modal fade" id="ewsModal" tabindex="-1" aria-labelledby="ewsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header bg-warning text-dark" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                <h5 class="modal-title fw-bold text-dark" id="ewsModalLabel"><i class="mdi mdi-heart-pulse me-1"></i> Update EWS - Bed <span id="modalEwsBedNum"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="ewsForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <input type="hidden" id="modalEwsEquipmentId" name="equipment_id">
                    
                    <div class="mb-3">
                        <label for="inputEwsStatus" class="form-label fw-bold text-dark"><i class="mdi mdi-shield-alert-outline text-warning me-1"></i> Status EWS</label>
                        <select class="form-select text-dark fw-bold" id="inputEwsStatus" name="ews">
                            <option value="">- Pilih Status EWS -</option>
                            <option value="Hijau" class="text-success fw-bold">Hijau</option>
                            <option value="Kuning" class="text-warning fw-bold">Kuning</option>
                            <option value="Orange" class="text-orange fw-bold">Orange</option>
                            <option value="Merah" class="text-danger fw-bold">Merah</option>
                            <option value="DNR" class="text-secondary fw-bold">DNR</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">
                    <button type="button" class="btn btn-secondary fw-bold px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="saveEwsBtn" class="btn btn-primary fw-bold px-4">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script AJAX untuk sinkronisasi, navigasi lantai, dan polling real-time -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Integrasi Manual Sinkronisasi Real-Time
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
                        // Reload the current dashboard state via AJAX
                        updateDashboardContent(window.location.href, true);
                        alert(data.message);
                    } else {
                        alert('Gagal: ' + data.message);
                    }
                    resetBtn();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Gagal terhubung ke server untuk sinkronisasi.');
                    resetBtn();
                });

                function resetBtn() {
                    btn.disabled = false;
                    icon.classList.remove('mdi-spin');
                    label.innerText = 'Sinkronisasi';
                }
            });
        }

        // 2. Real-Time AJAX Polling & Navigation
        let refreshInterval = null;
        const POLL_INTERVAL = 20000; // 20 seconds

        function updateDashboardContent(url, showLoading = false) {
            if (showLoading) {
                const container = document.getElementById('dashboard-layout-container');
                if (container) {
                    container.style.opacity = '0.5';
                    container.style.pointerEvents = 'none';
                }
            }

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // Swap Global Stats
                const oldStats = document.getElementById('global-stats-container');
                const newStats = doc.getElementById('global-stats-container');
                if (oldStats && newStats) {
                    oldStats.innerHTML = newStats.innerHTML;
                }

                // Swap Floor Dashboards
                const oldFloors = document.getElementById('floor-dashboards-container');
                const newFloors = doc.getElementById('floor-dashboards-container');
                if (oldFloors && newFloors) {
                    oldFloors.innerHTML = newFloors.innerHTML;
                    attachFloorCardListeners(); // Re-attach listeners to new cards
                }

                // Swap Detailed Layout
                const oldLayout = document.getElementById('dashboard-layout-container');
                const newLayout = doc.getElementById('dashboard-layout-container');
                if (oldLayout && newLayout) {
                    oldLayout.innerHTML = newLayout.innerHTML;
                    oldLayout.style.opacity = '1';
                    oldLayout.style.pointerEvents = 'auto';
                }
            })
            .catch(err => {
                console.error('Error refreshing dashboard:', err);
                const container = document.getElementById('dashboard-layout-container');
                if (container) {
                    container.style.opacity = '1';
                    container.style.pointerEvents = 'auto';
                }
            });
        }

        function attachFloorCardListeners() {
            const links = document.querySelectorAll('.floor-card-link');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.getAttribute('href');
                    
                    // Update URL in address bar without reloading
                    history.pushState(null, '', url);
                    
                    // Update content immediately
                    updateDashboardContent(url, true);
                });
            });
        }

        function startAutoRefresh() {
            stopAutoRefresh(); // clean up previous
            
            const liveIndicator = document.getElementById('liveIndicator');
            const liveText = document.getElementById('liveText');
            
            if (liveIndicator) {
                liveIndicator.classList.remove('live-dot-paused');
                liveIndicator.classList.add('live-dot-pulse');
            }
            if (liveText) {
                liveText.innerText = 'LIVE (20s)';
                liveText.classList.remove('text-muted');
                liveText.classList.add('text-dark');
            }

            refreshInterval = setInterval(() => {
                updateDashboardContent(window.location.href, false);
            }, POLL_INTERVAL);
        }

        function stopAutoRefresh() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
                refreshInterval = null;
            }
            
            const liveIndicator = document.getElementById('liveIndicator');
            const liveText = document.getElementById('liveText');
            
            if (liveIndicator) {
                liveIndicator.classList.remove('live-dot-pulse');
                liveIndicator.classList.add('live-dot-paused');
            }
            if (liveText) {
                liveText.innerText = 'PAUSED';
                liveText.classList.remove('text-dark');
                liveText.classList.add('text-muted');
            }
        }

        // Initialize listeners
        attachFloorCardListeners();

        // Handle Browser History Navigation
        window.addEventListener('popstate', function() {
            updateDashboardContent(window.location.href, true);
        });

        // Toggle Switch handler
        const switchEl = document.getElementById('autoRefreshSwitch');
        if (switchEl) {
            if (switchEl.checked) {
                startAutoRefresh();
            } else {
                stopAutoRefresh();
            }
            
            switchEl.addEventListener('change', function() {
                if (this.checked) {
                    startAutoRefresh();
                } else {
                    stopAutoRefresh();
                }
            });
        }

        // 3. Edit Ners Modal & Form Handlers
        const nersModalEl = document.getElementById('nersModal');
        const nersModal = nersModalEl ? new bootstrap.Modal(nersModalEl) : null;
        const nersForm = document.getElementById('nersForm');

        // We use event delegation since the container layout is swapped dynamically
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.edit-ners-btn');
            if (btn && nersModal) {
                e.preventDefault();
                const id = btn.getAttribute('data-id');
                const bedNum = btn.getAttribute('data-bed');
                const pagi = btn.getAttribute('data-pagi') || '';
                const siang = btn.getAttribute('data-siang') || '';
                const malam = btn.getAttribute('data-malam') || '';

                document.getElementById('modalEquipmentId').value = id;
                document.getElementById('modalBedNum').innerText = bedNum;
                
                document.getElementById('inputNersPagi').value = pagi;
                document.getElementById('inputNersSiang').value = siang;
                document.getElementById('inputNersMalam').value = malam;

                nersModal.show();
            }
        });

        if (nersForm) {
            nersForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const id = document.getElementById('modalEquipmentId').value;
                const pagi = document.getElementById('inputNersPagi').value;
                const siang = document.getElementById('inputNersSiang').value;
                const malam = document.getElementById('inputNersMalam').value;
                const submitBtn = document.getElementById('saveNersBtn');

                if (submitBtn) submitBtn.disabled = true;

                const url = `{{ url('/beds/nurses') }}/${id}`;
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        ners_pagi: pagi,
                        ners_siang: siang,
                        ners_malam: malam
                    })
                })
                .then(res => {
                    if (!res.ok) throw new Error('Update failed');
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        if (nersModal) nersModal.hide();
                        // Refresh data in dashboard instantly without page reload
                        updateDashboardContent(window.location.href, false);
                    } else {
                        alert('Gagal: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Gagal memperbarui data ners.');
                })
                .finally(() => {
                    if (submitBtn) submitBtn.disabled = false;
                });
            });
        }

        // 4. Edit EWS Modal & Form Handlers
        const ewsModalEl = document.getElementById('ewsModal');
        const ewsModal = ewsModalEl ? new bootstrap.Modal(ewsModalEl) : null;
        const ewsForm = document.getElementById('ewsForm');

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.edit-ews-btn');
            if (btn && ewsModal) {
                e.preventDefault();
                const id = btn.getAttribute('data-id');
                const bedNum = btn.getAttribute('data-bed');
                const ews = btn.getAttribute('data-ews') || '';

                document.getElementById('modalEwsEquipmentId').value = id;
                document.getElementById('modalEwsBedNum').innerText = bedNum;
                
                document.getElementById('inputEwsStatus').value = ews;

                ewsModal.show();
            }
        });

        if (ewsForm) {
            ewsForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const id = document.getElementById('modalEwsEquipmentId').value;
                const ews = document.getElementById('inputEwsStatus').value;
                const submitBtn = document.getElementById('saveEwsBtn');

                if (submitBtn) submitBtn.disabled = true;

                const url = `{{ url('/beds/ews') }}/${id}`;
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        ews: ews
                    })
                })
                .then(res => {
                    if (!res.ok) throw new Error('Update failed');
                    return res.json();
                })
                .then(data => {
                    if (data.success) {
                        if (ewsModal) ewsModal.hide();
                        // Refresh data in dashboard instantly without page reload
                        updateDashboardContent(window.location.href, false);
                    } else {
                        alert('Gagal: ' + data.message);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Gagal memperbarui data EWS.');
                })
                .finally(() => {
                    if (submitBtn) submitBtn.disabled = false;
                });
            });
        }
    });
</script>
@stop
