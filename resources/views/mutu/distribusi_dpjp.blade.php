@extends('layouts.staradmin')

@section('title', 'Distribusi DPJP & Lantai')

@section('content_header')
<div class="d-sm-flex align-items-center justify-content-between mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1" style="font-size: 0.85rem; padding: 0; background: none;">
                <li class="breadcrumb-item"><a href="#" class="text-decoration-none text-muted">Dashboard Mutu</a></li>
                <li class="breadcrumb-item active fw-bold" aria-current="page">Distribusi DPJP & Lantai</li>
            </ol>
        </nav>
        <h2 class="h3 font-weight-bold mb-1 text-dark d-flex align-items-center">
            Distribusi DPJP & Lantai
            <i class="mdi mdi-information-outline text-muted fs-5 ms-2" title="Laporan pemetaan dokter DPJP per lantai dan sebaran rawat pasien"></i>
        </h2>
        <p class="text-muted mb-0" style="font-size: 0.85rem;">Pemetaan sebaran tugas visite DPJP Utama dan rawat pasien per lantai secara real-time.</p>
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
    .table-hover tbody tr.accordion-toggle:hover {
        background-color: #f1f4ff !important;
        cursor: pointer;
    }
    .cursor-pointer {
        cursor: pointer;
    }
    .btn-toggle-collapse::after {
        display: inline-block;
        margin-left: 0.255em;
        vertical-align: 0.255em;
        content: "";
        border-top: 0.3em solid;
        border-right: 0.3em solid transparent;
        border-bottom: 0;
        border-left: 0.3em solid transparent;
        transition: transform 0.2s ease;
    }
    .btn-toggle-collapse.collapsed::after {
        transform: rotate(-90deg);
    }
</style>

<!-- TABS NAVIGATION -->
<div class="row mb-4">
    <div class="col-12">
        <ul class="nav nav-tabs nav-tabs-bordered border-bottom-2" id="mutuDpjpTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold text-dark fs-5 py-2.5 px-4" id="floor-tab" data-bs-toggle="tab" data-bs-target="#floor-pane" type="button" role="tab" aria-controls="floor-pane" aria-selected="true">
                    <i class="mdi mdi-office-building me-1.5 text-primary"></i> Laporan DPJP per Lantai
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold text-dark fs-5 py-2.5 px-4" id="doctor-tab" data-bs-toggle="tab" data-bs-target="#doctor-pane" type="button" role="tab" aria-controls="doctor-pane" aria-selected="false">
                    <i class="mdi mdi-doctor me-1.5 text-success"></i> Laporan Detail Per DPJP
                </button>
            </li>
        </ul>
    </div>
</div>

<div class="tab-content" id="mutuDpjpTabsContent">
    
    <!-- PANE 1: DPJP PER LANTAI -->
    <div class="tab-pane fade show active" id="floor-pane" role="tabpanel" aria-labelledby="floor-tab" tabindex="0">
        
        <!-- Search Filter Floor -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="mdi mdi-magnify text-muted"></i></span>
                    <input type="text" id="searchFloor" class="form-control border-start-0" placeholder="Cari lantai..." onkeyup="filterFloors()">
                </div>
            </div>
            <div class="col-md-8 text-md-end mt-2 mt-md-0">
                <span class="badge bg-light text-dark fw-bold border p-2">
                    Total Pasien Dirawat: {{ $patients->count() }} Orang
                </span>
            </div>
        </div>

        <div class="row" id="floors-container">
            @forelse($floorReport as $floorName => $floorData)
                @php
                    $totalDoctors = count($floorData['doctors']);
                    $totalPatientsInFloor = array_sum(array_column($floorData['doctors'], 'patient_count'));
                    $floorSafeId = 'floor_' . Str::slug($floorName);
                @endphp
                <div class="col-12 mb-4 floor-card-wrapper" data-floor="{{ strtolower($floorName) }}">
                    <div class="card card-mutu border-0">
                        <div class="card-header bg-light border-0 py-3.5 px-4 d-flex justify-content-between align-items-center" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                            <h4 class="mb-0 fw-bold text-dark d-flex align-items-center">
                                <i class="mdi mdi-hospital-building text-primary me-2 fs-4"></i>
                                {{ $floorName }}
                            </h4>
                            <div class="d-flex gap-2">
                                <span class="badge bg-primary text-white fw-bold px-3 py-2 fs-7">
                                    <i class="mdi mdi-doctor me-1"></i> {{ $totalDoctors }} Dokter DPJP
                                </span>
                                <span class="badge bg-dark text-white fw-bold px-3 py-2 fs-7">
                                    <i class="mdi mdi-account-group me-1"></i> {{ $totalPatientsInFloor }} Pasien
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle border-0 mb-0">
                                    <thead class="table-light text-uppercase" style="font-size: 0.8rem;">
                                        <tr>
                                            <th class="w-60" style="padding-left: 1.5rem;">Nama Dokter / DPJP</th>
                                            <th class="text-center w-25">Jumlah Pasien</th>
                                            <th class="text-end" style="padding-right: 1.5rem;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($floorData['doctors'] as $doctorName => $docData)
                                            @php
                                                $collapseId = 'collapseFloor_' . Str::slug($floorName) . '_' . $loop->index;
                                            @endphp
                                            <!-- Doctor Row Trigger -->
                                            <tr class="accordion-toggle" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">
                                                <td class="fw-bold text-dark" style="padding-left: 1.5rem;">
                                                    <i class="mdi mdi-chevron-right text-muted me-2 transition-icon"></i>
                                                    {{ $doctorName }}
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-primary border border-primary-subtle fw-bold fs-6 px-2.5 py-1.5">
                                                        {{ $docData['patient_count'] }} Pasien
                                                    </span>
                                                </td>
                                                <td class="text-end" style="padding-right: 1.5rem;">
                                                    <button class="btn btn-outline-primary btn-xs fw-bold px-3 py-1 btn-toggle-collapse collapsed" 
                                                            data-bs-toggle="collapse" 
                                                            data-bs-target="#{{ $collapseId }}">
                                                        Lihat Pasien
                                                    </button>
                                                </td>
                                            </tr>
                                            <!-- Collapsible Patient Details Table -->
                                            <tr class="p-0 border-0">
                                                <td colspan="3" class="p-0 border-0">
                                                    <div class="collapse" id="{{ $collapseId }}">
                                                        <div class="px-4 py-3 bg-light bg-opacity-50 border-bottom border-top">
                                                            <h6 class="fw-bold text-secondary mb-2.5 small text-uppercase">
                                                                <i class="mdi mdi-account-group text-primary me-1"></i> 
                                                                Pasien dr. {{ $doctorName }} di {{ $floorName }}
                                                            </h6>
                                                            <div class="table-responsive">
                                                                <table class="table table-sm table-bordered bg-white mb-0 shadow-sm" style="border-radius: 8px; overflow: hidden;">
                                                                    <thead class="table-dark">
                                                                        <tr class="small">
                                                                            <th class="py-2.5">Nama Pasien</th>
                                                                            <th class="py-2.5 text-center">No. RM</th>
                                                                            <th class="py-2.5">Ruangan / Kamar</th>
                                                                            <th class="py-2.5 text-center">Kelas</th>
                                                                            <th class="py-2.5">Diagnosis Utama</th>
                                                                            <th class="py-2.5 text-center">Tgl Masuk</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($docData['patients'] as $p)
                                                                            <tr class="small">
                                                                                <td>
                                                                                    <a href="{{ route('maintenances.patient_detail', $p['serial_number']) }}" class="text-decoration-none fw-bold text-primary hover-underline">
                                                                                        {{ $p['name'] }}
                                                                                    </a>
                                                                                </td>
                                                                                <td class="text-center"><code>{{ $p['serial_number'] }}</code></td>
                                                                                <td>{{ $p['room'] }}</td>
                                                                                <td class="text-center"><span class="badge bg-secondary text-white">{{ $p['class'] }}</span></td>
                                                                                <td class="text-truncate" style="max-width: 250px;" title="{{ $p['diagnosa'] }}">{{ $p['diagnosa'] }}</td>
                                                                                <td class="text-center text-muted">{{ $p['registered_date'] }}</td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <i class="mdi mdi-account-off text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3 text-dark fw-bold">Belum Ada Data Pasien Rawat Inap Aktif</h4>
                </div>
            @endforelse
        </div>

    </div>

    <!-- PANE 2: DETIL PER DPJP -->
    <div class="tab-pane fade" id="doctor-pane" role="tabpanel" aria-labelledby="doctor-tab" tabindex="0">
        
        <!-- Search Filter Doctor -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="mdi mdi-magnify text-muted"></i></span>
                    <input type="text" id="searchDoctor" class="form-control border-start-0" placeholder="Cari nama dokter..." onkeyup="filterDoctors()">
                </div>
            </div>
            <div class="col-md-8 text-md-end mt-2 mt-md-0">
                <span class="badge bg-light text-dark fw-bold border p-2">
                    Total DPJP Aktif: {{ count($dpjpReport) }} Dokter
                </span>
            </div>
        </div>

        <div class="row" id="doctors-container">
            @forelse($dpjpReport as $doctorName => $doctorData)
                @php
                    $docSafeId = 'doc_' . Str::slug($doctorName);
                @endphp
                <div class="col-lg-6 mb-4 doctor-card-wrapper" data-doctor="{{ strtolower($doctorName) }}">
                    <div class="card card-mutu border-0 h-100">
                        <div class="card-header bg-light border-0 py-3 px-3.5 d-flex justify-content-between align-items-center" style="border-top-left-radius: 12px; border-top-right-radius: 12px;">
                            <div>
                                <h4 class="mb-0 fw-bold text-dark fs-5 text-truncate" style="max-width: 250px;" title="{{ $doctorName }}">
                                    <i class="mdi mdi-doctor text-success me-1.5"></i>
                                    {{ $doctorName }}
                                </h4>
                                <span class="badge bg-success bg-opacity-10 text-success fw-bold p-1 mt-1 small" style="font-size: 0.72rem;">
                                    Spesialisasi: {{ $doctorData['spesialis'] }}
                                </span>
                            </div>
                            <span class="badge bg-dark text-white fw-bold px-3 py-2 fs-6">
                                {{ $doctorData['total_patients'] }} Pasien
                            </span>
                        </div>
                        <div class="card-body p-3.5">
                            <h6 class="fw-bold text-muted small text-uppercase mb-2"><i class="mdi mdi-map-marker text-muted me-1"></i> Sebaran Lantai Rawat:</h6>
                            <div class="accordion accordion-flush border rounded overflow-hidden" id="accordionDoc_{{ $docSafeId }}">
                                @foreach($doctorData['floors'] as $floorName => $floorData)
                                    @php
                                        $headerId = 'heading_' . $docSafeId . '_' . $loop->index;
                                        $bodyId = 'body_' . $docSafeId . '_' . $loop->index;
                                    @endphp
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="{{ $headerId }}">
                                            <button class="accordion-button collapsed fw-bold py-2.5 px-3" style="font-size: 0.88rem; background-color: #fafafa;" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $bodyId }}" aria-expanded="false" aria-controls="{{ $bodyId }}">
                                                <i class="mdi mdi-layers-outline me-2 text-primary"></i>
                                                {{ $floorName }}
                                                <span class="badge bg-light text-primary border border-primary-subtle fw-bold ms-2" style="font-size: 0.72rem;">
                                                    {{ $floorData['patient_count'] }} Pasien
                                                </span>
                                            </button>
                                        </h2>
                                        <div id="{{ $bodyId }}" class="accordion-collapse collapse" aria-labelledby="{{ $headerId }}" data-bs-parent="#accordionDoc_{{ $docSafeId }}">
                                            <div class="accordion-body p-2 bg-light">
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered bg-white mb-0 shadow-xs" style="font-size: 0.82rem;">
                                                        <thead class="table-dark">
                                                            <tr>
                                                                <th>Nama Pasien</th>
                                                                <th class="text-center">RM</th>
                                                                <th>Ruangan</th>
                                                                <th class="text-center">Kls</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($floorData['patients'] as $p)
                                                                <tr>
                                                                    <td>
                                                                        <a href="{{ route('maintenances.patient_detail', $p['serial_number']) }}" class="text-decoration-none fw-bold text-primary hover-underline">
                                                                            {{ $p['name'] }}
                                                                        </a>
                                                                    </td>
                                                                    <td class="text-center"><code>{{ $p['serial_number'] }}</code></td>
                                                                    <td class="text-truncate" style="max-width: 130px;" title="{{ $p['room'] }}">{{ $p['room'] }}</td>
                                                                    <td class="text-center"><span class="badge bg-secondary text-white py-0.5 px-1.5">{{ $p['class'] }}</span></td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <i class="mdi mdi-doctor-off text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3 text-dark fw-bold">Belum Ada Dokter Terdaftar</h4>
                </div>
            @endforelse
        </div>

    </div>

</div>

<!-- JavaScript Filtering -->
<script>
    // Filter lantai
    function filterFloors() {
        const query = document.getElementById('searchFloor').value.toLowerCase();
        const wrappers = document.querySelectorAll('.floor-card-wrapper');
        
        wrappers.forEach(wrap => {
            const floor = wrap.getAttribute('data-floor');
            if (floor.includes(query)) {
                wrap.style.display = 'block';
            } else {
                wrap.style.display = 'none';
            }
        });
    }

    // Filter dokter
    function filterDoctors() {
        const query = document.getElementById('searchDoctor').value.toLowerCase();
        const wrappers = document.querySelectorAll('.doctor-card-wrapper');
        
        wrappers.forEach(wrap => {
            const doc = wrap.getAttribute('data-doctor');
            if (doc.includes(query)) {
                wrap.style.display = 'block';
            } else {
                wrap.style.display = 'none';
            }
        });
    }

    // Listen Bootstrap collapse events to toggle indicator icons
    document.addEventListener('DOMContentLoaded', function () {
        const collapses = document.querySelectorAll('.accordion-toggle');
        collapses.forEach(el => {
            el.addEventListener('click', function () {
                const icon = el.querySelector('.transition-icon');
                const btn = el.querySelector('.btn-toggle-collapse');
                
                // Delay checks to allow Bootstrap classes to shift
                setTimeout(() => {
                    const isExpanded = el.getAttribute('aria-expanded') === 'true';
                    if (isExpanded) {
                        if (icon) icon.style.transform = 'rotate(90deg)';
                        if (btn) btn.classList.remove('collapsed');
                    } else {
                        if (icon) icon.style.transform = 'rotate(0deg)';
                        if (btn) btn.classList.add('collapsed');
                    }
                }, 150);
            });
        });
    });
</script>

@stop
