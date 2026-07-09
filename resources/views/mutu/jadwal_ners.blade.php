@extends('layouts.staradmin')

@section('title', 'Laporan Shift Ners')

@section('content_header')
<div class="d-sm-flex align-items-center justify-content-between mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.85rem; padding: 0; background: none;">
                <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted">Dashboard Mutu</a></li>
                <li class="breadcrumb-item active fw-bold" aria-current="page">Laporan Shift Ners</li>
            </ol>
        </nav>
        <h2 class="h3 font-weight-bold mb-1 text-dark d-flex align-items-center">
            Laporan Shift Ners
            <i class="mdi mdi-information-outline text-muted fs-5 ms-2" title="Laporan pembagian tugas shift perawat (ners) berdasarkan tanggal, shift, dan lokasi lantai rawat"></i>
        </h2>
        <p class="text-muted mb-0" style="font-size: 0.85rem;">Pemetaan tugas Ners (Pagi, Siang, Malam) per lantai dan daftar pasien yang dirawat.</p>
    </div>
    <div class="d-flex gap-2 mt-3 mt-sm-0 align-items-center">
        <span class="text-muted me-3" style="font-size: 0.85rem;">Data terakhir: {{ now()->format('d F Y H:i') }} WIB <i class="mdi mdi-refresh ms-1" style="cursor:pointer;" onclick="location.reload();"></i></span>
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
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .card-mutu:hover {
        transform: translateY(-2px);
        box-shadow: 0px 8px 24px rgba(0, 0, 0, 0.06);
    }
    .shift-pill {
        font-size: 0.75rem;
        font-weight: 700;
        padding: 4px 8px;
        border-radius: 4px;
    }
    .shift-pagi { background-color: #e3f2fd; color: #0d6efd; }
    .shift-siang { background-color: #fff3cd; color: #ffc107; }
    .shift-malam { background-color: #f8f9fa; color: #343a40; border: 1px solid #dee2e6; }
</style>

<!-- FILTER & STATS CARD -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card card-mutu border-0">
            <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <form action="{{ route('mutu.jadwal-ners') }}" method="GET" id="filterForm" class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="d-flex align-items-center gap-2">
                        <label for="dateFrom" class="fw-bold text-dark mb-0 text-nowrap"><i class="mdi mdi-calendar-range text-primary me-1"></i> Dari:</label>
                        <input type="date" name="date_from" id="dateFrom" class="form-control form-control-sm text-dark fw-bold" value="{{ $dateFrom }}" onchange="document.getElementById('filterForm').submit();" style="width: 160px;">
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <label for="dateTo" class="fw-bold text-dark mb-0 text-nowrap"><i class="mdi mdi-calendar-range text-primary me-1"></i> Sampai:</label>
                        <input type="date" name="date_to" id="dateTo" class="form-control form-control-sm text-dark fw-bold" value="{{ $dateTo }}" onchange="document.getElementById('filterForm').submit();" style="width: 160px;">
                    </div>
                    @if($dateFrom !== now()->toDateString() || $dateTo !== now()->toDateString())
                        <a href="{{ route('mutu.jadwal-ners') }}" class="btn btn-sm btn-light border fw-bold shadow-sm">
                            <i class="mdi mdi-refresh me-1"></i> Reset
                        </a>
                    @endif
                </form>
                
                <div class="d-flex flex-wrap gap-2">
                    @if($dateFrom !== $dateTo)
                        <span class="badge bg-warning text-dark p-2.5 fw-bold fs-7 rounded shadow-xs">
                            <i class="mdi mdi-calendar-range me-1"></i> {{ \Carbon\Carbon::parse($dateFrom)->format('d M') }} – {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                        </span>
                    @endif
                    <span class="badge bg-primary text-white p-2.5 fw-bold fs-7 rounded shadow-xs">
                        <i class="mdi mdi-account-multiple me-1"></i> {{ count($nurseReports) }} Ners Tugas
                    </span>
                    <span class="badge bg-dark text-white p-2.5 fw-bold fs-7 rounded shadow-xs">
                        <i class="mdi mdi-bed-outline me-1"></i> {{ $patients->count() }} Pasien Aktif
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- TABS NAVIGATION -->
<div class="row mb-4">
    <div class="col-12">
        <ul class="nav nav-tabs nav-tabs-bordered border-bottom-2" id="nersTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold text-dark fs-5 py-2.5 px-4" id="nurse-tab" data-bs-toggle="tab" data-bs-target="#nurse-pane" type="button" role="tab" aria-controls="nurse-pane" aria-selected="true">
                    <i class="mdi mdi-account-star-outline me-1.5 text-primary"></i> Laporan Per Ners
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold text-dark fs-5 py-2.5 px-4" id="shift-tab" data-bs-toggle="tab" data-bs-target="#shift-pane" type="button" role="tab" aria-controls="shift-pane" aria-selected="false">
                    <i class="mdi mdi-clock-outline me-1.5 text-warning"></i> Laporan Per Shift
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold text-dark fs-5 py-2.5 px-4" id="floor-tab" data-bs-toggle="tab" data-bs-target="#floor-pane" type="button" role="tab" aria-controls="floor-pane" aria-selected="false">
                    <i class="mdi mdi-office-building me-1.5 text-success"></i> Laporan Per Lantai
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold text-dark fs-5 py-2.5 px-4" id="logbook-tab" data-bs-toggle="tab" data-bs-target="#logbook-pane" type="button" role="tab" aria-controls="logbook-pane" aria-selected="false">
                    <i class="mdi mdi-book-open-page-variant me-1.5 text-info"></i> Histori & Logbook Ners
                </button>
            </li>
        </ul>
    </div>
</div>

<div class="tab-content" id="nersTabsContent">
    
    <!-- PANE 1: LAPORAN PER NERS -->
    <div class="tab-pane fade show active" id="nurse-pane" role="tabpanel" aria-labelledby="nurse-tab" tabindex="0">
        
        <!-- Live Search Nurse -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="mdi mdi-magnify text-muted"></i></span>
                    <input type="text" id="searchNurse" class="form-control border-start-0" placeholder="Cari nama Ners..." onkeyup="filterNurses()">
                </div>
            </div>
        </div>

        <div class="row" id="nurses-container">
            @forelse($nurseReports as $nurseName => $nData)
                <div class="col-md-6 mb-4 nurse-card-wrapper" data-nurse="{{ strtolower($nurseName) }}">
                    <div class="card card-mutu border-0 h-100">
                        <div class="card-header bg-light border-0 py-3 px-3.5 d-flex justify-content-between align-items-center" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                            <h4 class="mb-0 fw-bold text-dark"><i class="mdi mdi-account-circle text-primary me-2"></i> {{ $nurseName }}</h4>
                        </div>
                        <div class="card-body p-3.5">
                            @foreach($nData['shifts'] as $shiftName => $sData)
                                @php
                                    $pillClass = $shiftName === 'Pagi' ? 'shift-pagi' : ($shiftName === 'Siang' ? 'shift-siang' : 'shift-malam');
                                    $icon = $shiftName === 'Pagi' ? '🌅' : ($shiftName === 'Siang' ? '☀️' : '🌙');
                                @endphp
                                <div class="mb-3 border-bottom pb-2">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="shift-pill {{ $pillClass }} me-2">{{ $icon }} Shift {{ $shiftName }}</span>
                                    </div>
                                    @foreach($sData['floors'] as $floorName => $fData)
                                        <div class="ms-3 mb-2">
                                            <strong class="text-secondary small d-block mb-1"><i class="mdi mdi-map-marker text-danger"></i> {{ $floorName }}</strong>
                                            <ul class="list-unstyled mb-0 ps-1">
                                                @foreach($fData['patients'] as $p)
                                                    <li class="py-1 border-bottom border-light d-flex justify-content-between align-items-center">
                                                        <span class="text-dark fw-bold">{{ $p['name'] }} <code class="small text-muted">({{ $p['serial_number'] }})</code></span>
                                                        <span class="text-muted small"><i class="mdi mdi-hotel text-info"></i> {{ $p['room'] }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <i class="mdi mdi-account-off text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3 text-dark fw-bold">Tidak Ada Ners yang Bertugas pada Tanggal Ini</h4>
                </div>
            @endforelse
        </div>
    </div>

    <!-- PANE 2: LAPORAN PER SHIFT -->
    <div class="tab-pane fade" id="shift-pane" role="tabpanel" aria-labelledby="shift-tab" tabindex="0">
        <div class="row">
            @foreach(['Pagi', 'Siang', 'Malam'] as $sName)
                @php
                    $sData = $shiftReports[$sName] ?? [];
                    $pillClass = $sName === 'Pagi' ? 'shift-pagi' : ($sName === 'Siang' ? 'shift-siang' : 'shift-malam');
                    $icon = $sName === 'Pagi' ? '🌅' : ($sName === 'Siang' ? '☀️' : '🌙');
                @endphp
                <div class="col-lg-4 mb-4">
                    <div class="card card-mutu border-0 h-100">
                        <div class="card-header bg-light border-0 py-3.5 px-3" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                            <h4 class="mb-0 fw-bold text-dark d-flex align-items-center">
                                <span class="shift-pill {{ $pillClass }} fs-6 py-2 px-3 me-2">{{ $icon }} SHIFT {{ strtoupper($sName) }}</span>
                            </h4>
                        </div>
                        <div class="card-body p-3">
                            @forelse($sData as $floorName => $nList)
                                <div class="mb-4">
                                    <h5 class="fw-bold text-primary mb-2.5" style="font-size: 0.95rem;">
                                        <i class="mdi mdi-office-building text-primary me-1"></i> {{ $floorName }}
                                    </h5>
                                    @foreach($nList as $nurseName => $nInfo)
                                        <div class="ms-3 mb-3 p-2 bg-light rounded border">
                                            <strong class="text-dark small d-block mb-1.5"><i class="mdi mdi-account-star text-success"></i> Ners: {{ $nurseName }}</strong>
                                            <table class="table table-sm table-bordered bg-white mb-0" style="font-size: 0.8rem;">
                                                <thead>
                                                    <tr class="table-dark">
                                                        <th class="py-1">Pasien</th>
                                                        <th class="py-1">Bed</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($nInfo['patients'] as $p)
                                                        <tr>
                                                            <td><b>{{ $p['name'] }}</b></td>
                                                            <td>{{ $p['room'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endforeach
                                </div>
                            @empty
                                <div class="text-center py-4 text-muted">
                                    <i class="mdi mdi-clock-off" style="font-size: 2rem;"></i>
                                    <p class="mt-2 mb-0">Tidak ada penugasan shift ini</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- PANE 3: LAPORAN PER LANTAI -->
    <div class="tab-pane fade" id="floor-pane" role="tabpanel" aria-labelledby="floor-tab" tabindex="0">
        <div class="row">
            @forelse($floorReports as $floorName => $sList)
                <div class="col-md-6 mb-4">
                    <div class="card card-mutu border-0 h-100">
                        <div class="card-header bg-light border-0 py-3.5 px-3.5" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                            <h4 class="mb-0 fw-bold text-dark"><i class="mdi mdi-hospital-building text-primary me-2"></i> {{ $floorName }}</h4>
                        </div>
                        <div class="card-body p-3.5">
                            @foreach($sList as $shiftName => $nList)
                                @php
                                    $pillClass = $shiftName === 'Pagi' ? 'shift-pagi' : ($shiftName === 'Siang' ? 'shift-siang' : 'shift-malam');
                                    $icon = $shiftName === 'Pagi' ? '🌅' : ($shiftName === 'Siang' ? '☀️' : '🌙');
                                @endphp
                                <div class="mb-3 pb-2 border-bottom">
                                    <span class="shift-pill {{ $pillClass }} d-inline-block mb-2">{{ $icon }} Shift {{ $shiftName }}</span>
                                    
                                    @foreach($nList as $nurseName => $nInfo)
                                        <div class="ms-3 mb-2">
                                            <strong class="text-secondary small d-block mb-1"><i class="mdi mdi-account-star text-success"></i> Ners: {{ $nurseName }}</strong>
                                            <ul class="list-unstyled mb-0 ps-2">
                                                @foreach($nInfo['patients'] as $p)
                                                    <li class="py-1 border-bottom border-light text-dark small">
                                                        <i class="mdi mdi-chevron-right text-muted me-1"></i>
                                                        {{ $p['name'] }} <code class="small">({{ $p['serial_number'] }})</code> - <span class="text-muted">{{ $p['room'] }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <i class="mdi mdi-office-building-off text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3 text-dark fw-bold">Belum Ada Data Ruangan</h4>
                </div>
            @endforelse
        </div>
    </div>

    <!-- PANE 4: HISTORI & LOGBOOK NERS -->
    <div class="tab-pane fade" id="logbook-pane" role="tabpanel" aria-labelledby="logbook-tab" tabindex="0">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-mutu border-0">
                    <div class="card-body p-4">
                        <form action="{{ route('mutu.jadwal-ners') }}" method="GET" id="logbookFilterForm" class="d-flex flex-wrap align-items-end gap-3">
                            <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                            <input type="hidden" name="date_to" value="{{ $dateTo }}">
                            <input type="hidden" name="tab" value="logbook">

                            <div style="min-width: 250px;">
                                <label class="fw-bold text-dark mb-1.5"><i class="mdi mdi-account-star text-primary me-1"></i> Pilih Personal Ners:</label>
                                <select name="nurse_name" class="form-select fw-bold text-dark" style="font-size: 0.9rem;" onchange="document.getElementById('logbookFilterForm').submit();">
                                    <option value="">-- Pilih Ners --</option>
                                    @foreach($nursesList as $nr)
                                        <option value="{{ $nr->name }}" {{ $selectedNurse == $nr->name ? 'selected' : '' }}>{{ $nr->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div style="min-width: 200px;">
                                <label class="fw-bold text-dark mb-1.5"><i class="mdi mdi-calendar-month text-primary me-1"></i> Pilih Bulan:</label>
                                <input type="month" name="month" class="form-control fw-bold text-dark" style="font-size: 0.9rem;" value="{{ $selectedMonth }}" onchange="document.getElementById('logbookFilterForm').submit();">
                            </div>

                            <div>
                                <button type="submit" class="btn btn-primary fw-bold text-white shadow-sm" style="height: 38px;">
                                    <i class="mdi mdi-magnify me-1"></i> Tampilkan Logbook
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @if($selectedNurse)
            <!-- SCORECARDS LOGBOOK -->
            <div class="row mb-4 g-3">
                <div class="col-md-3">
                    <div class="card card-mutu h-100 border-0 bg-primary text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); border-radius: 12px;">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white opacity-75 fw-bold mb-1" style="font-size: 0.85rem;">Total Pasien Dipegang</h6>
                                <h2 class="fw-bolder mb-0 text-white" style="font-size: 2.3rem;">{{ $totalLogbookPatients }}</h2>
                                <p class="text-white opacity-75 fw-bold mb-0 mt-1" style="font-size: 0.8rem;">Bulan: {{ Carbon\Carbon::parse($selectedMonth.'-01')->format('F Y') }}</p>
                            </div>
                            <i class="mdi mdi-account-multiple text-white opacity-25 fs-1"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-mutu h-100 border-0 bg-success text-white" style="background: linear-gradient(135deg, #198754 0%, #146c43 100%); border-radius: 12px;">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white opacity-75 fw-bold mb-1" style="font-size: 0.85rem;">Shift Pagi</h6>
                                <h2 class="fw-bolder mb-0 text-white" style="font-size: 2.1rem;">{{ $shiftCounts['Pagi'] }}</h2>
                                <p class="text-white opacity-75 fw-bold mb-0 mt-1" style="font-size: 0.8rem;">Pasien dipegang</p>
                            </div>
                            <span class="fs-1 opacity-25">🌅</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-mutu h-100 border-0 bg-warning text-dark" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); border-radius: 12px;">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-dark opacity-75 fw-bold mb-1" style="font-size: 0.85rem;">Shift Siang</h6>
                                <h2 class="fw-bolder mb-0 text-dark" style="font-size: 2.1rem;">{{ $shiftCounts['Siang'] }}</h2>
                                <p class="text-dark opacity-75 fw-bold mb-0 mt-1" style="font-size: 0.8rem;">Pasien dipegang</p>
                            </div>
                            <span class="fs-1 opacity-25">☀️</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-mutu h-100 border-0 bg-dark text-white" style="background: linear-gradient(135deg, #212529 0%, #1c1f23 100%); border-radius: 12px;">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-white opacity-75 fw-bold mb-1" style="font-size: 0.85rem;">Shift Malam</h6>
                                <h2 class="fw-bolder mb-0 text-white" style="font-size: 2.1rem;">{{ $shiftCounts['Malam'] }}</h2>
                                <p class="text-white opacity-75 fw-bold mb-0 mt-1" style="font-size: 0.8rem;">Pasien dipegang</p>
                            </div>
                            <span class="fs-1 opacity-25">🌙</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABEL LOGBOOK -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-mutu border-0">
                        <div class="card-header bg-white border-bottom-0 py-3.5 px-4 d-flex justify-content-between align-items-center" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                            <h4 class="mb-0 fw-bold text-dark"><i class="mdi mdi-book-open-page-variant text-info me-2"></i> Logbook Pasien Ners: {{ $selectedNurse }}</h4>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle border">
                                    <thead style="background: #f8f9fa;">
                                        <tr>
                                            <th class="text-center" style="width: 50px;">No</th>
                                            <th style="width: 130px;">Tanggal</th>
                                            <th style="width: 120px;">Shift</th>
                                            <th>No. RM</th>
                                            <th>Nama Pasien</th>
                                            <th>Lokasi Rawat (Bed)</th>
                                            <th>Diagnosa Medis</th>
                                            <th>Keterangan Tugas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($logbookData as $idx => $log)
                                            @php
                                                $pillClass = $log['shift'] === 'Pagi' ? 'shift-pagi' : ($log['shift'] === 'Siang' ? 'shift-siang' : 'shift-malam');
                                                $icon = $log['shift'] === 'Pagi' ? '🌅' : ($log['shift'] === 'Siang' ? '☀️' : '🌙');
                                            @endphp
                                            <tr>
                                                <td class="text-center fw-bold">{{ $idx + 1 }}</td>
                                                <td>{{ date('d/m/Y', strtotime($log['tanggal'])) }}</td>
                                                <td><span class="shift-pill {{ $pillClass }}">{{ $icon }} Shift {{ $log['shift'] }}</span></td>
                                                <td class="fw-bold">{{ $log['no_rm'] }}</td>
                                                <td class="text-primary fw-bold">{{ $log['nama'] }}</td>
                                                <td><span class="text-muted"><i class="mdi mdi-hotel text-info me-1"></i> {{ $log['ruangan'] }}</span></td>
                                                <td>{{ $log['diagnosa'] }}</td>
                                                <td><code class="small text-muted">{{ $log['keterangan'] }}</code></td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-5">
                                                    <i class="mdi mdi-book-remove text-muted" style="font-size: 3.5rem;"></i>
                                                    <h5 class="text-muted mt-3">Tidak ada data logbook pasien untuk Ners ini pada bulan ini.</h5>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="row">
                <div class="col-12 text-center py-5">
                    <i class="mdi mdi-book-search text-muted" style="font-size: 4.5rem;"></i>
                    <h4 class="mt-3 text-dark fw-bold">Silakan Pilih Personal Ners Terlebih Dahulu</h4>
                    <p class="text-muted fs-6">Pilih nama Ners dan bulan di dropdown atas untuk melihat logbook & histori pasien yang ditangani.</p>
                </div>
            </div>
        @endif
    </div>

</div>

<!-- JavaScript Filtering -->
<script>
    function filterNurses() {
        const query = document.getElementById('searchNurse').value.toLowerCase();
        const wrappers = document.querySelectorAll('.nurse-card-wrapper');
        
        wrappers.forEach(wrap => {
            const nurse = wrap.getAttribute('data-nurse');
            if (nurse.includes(query)) {
                wrap.style.display = 'block';
            } else {
                wrap.style.display = 'none';
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function() {
        // Switch tab on load if specified in URL
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        const hasNurse = urlParams.has('nurse_name');
        
        if (tabParam === 'logbook' || hasNurse) {
            const logbookTab = document.querySelector('#logbook-tab');
            if (logbookTab) {
                const tab = new bootstrap.Tab(logbookTab);
                tab.show();
            }
        }
    });
</script>

@stop
