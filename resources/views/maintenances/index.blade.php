@extends('layouts.staradmin')

@section('title', 'Catatan Monitoring Harian Pasien Rawat Inap')

@section('content_header')
<div class="d-sm-flex align-items-center justify-content-between mb-2">
    <div class="d-flex align-items-center">
        <div class="bg-danger text-white rounded p-2.5 me-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
            <i class="mdi mdi-clipboard-text-play fs-3"></i>
        </div>
        <div>
            <h2 class="h3 font-weight-bold mb-0 text-danger" style="letter-spacing: -0.5px;">CATATAN MONITORING HARIAN PASIEN RAWAT INAP</h2>
        </div>
    </div>
    <div class="d-flex gap-2 mt-3 mt-sm-0 align-items-center flex-wrap">
        <!-- Search + Sort Form -->
        <form action="{{ route('maintenances.index') }}" method="GET" class="d-flex gap-2 flex-wrap align-items-center" id="monitoringFilterForm">
            @if(request('lantai'))
                <input type="hidden" name="lantai" value="{{ request('lantai') }}">
            @endif
            @if(request('wing'))
                <input type="hidden" name="wing" value="{{ request('wing') }}">
            @endif
            @if(request('room'))
                <input type="hidden" name="room" value="{{ request('room') }}">
            @endif

            <!-- Search -->
            <div class="input-group shadow-sm" style="min-width: 260px;">
                <span class="input-group-text bg-white border-end-0"><i class="mdi mdi-magnify text-muted fs-5"></i></span>
                <input type="text" name="search" class="form-control border-start-0 ps-0 bg-white fw-bold text-dark" placeholder="Cari nama / No. RM / DPJP / Ruangan" value="{{ request('search') }}" style="font-size: 0.92rem;">
            </div>

            <!-- Sort by Ruangan filter input -->
            <div class="input-group shadow-sm" style="min-width: 180px;">
                <span class="input-group-text bg-white border-end-0 py-0" style="font-size: 0.85rem;"><i class="mdi mdi-bed-outline text-muted"></i></span>
                <input type="text" name="filter_ruangan" class="form-control border-start-0 ps-0 bg-white fw-bold text-dark" placeholder="Filter ruangan..." value="{{ request('filter_ruangan') }}" style="font-size: 0.88rem;">
            </div>

            <!-- Sort Buttons -->
            <div class="btn-group shadow-sm" role="group">
                <a href="{{ route('maintenances.index', array_merge(request()->except(['sort', 'page']), ['sort' => 'terbaru'])) }}"
                   class="btn btn-sm fw-bold {{ request('sort', 'terbaru') === 'terbaru' ? 'btn-danger text-white' : 'btn-light border text-dark bg-white' }}" style="font-size: 0.82rem;" title="Urut: Terbaru masuk">
                    <i class="mdi mdi-sort-clock-descending-outline me-1"></i>Terbaru
                </a>
                <a href="{{ route('maintenances.index', array_merge(request()->except(['sort', 'page']), ['sort' => 'ruangan'])) }}"
                   class="btn btn-sm fw-bold {{ request('sort') === 'ruangan' ? 'btn-primary text-white' : 'btn-light border text-dark bg-white' }}" style="font-size: 0.82rem;" title="Urut per Ruangan A-Z">
                    <i class="mdi mdi-hospital-building me-1"></i>Ruangan
                </a>
                <a href="{{ route('maintenances.index', array_merge(request()->except(['sort', 'page']), ['sort' => 'los_terlama'])) }}"
                   class="btn btn-sm fw-bold {{ request('sort') === 'los_terlama' ? 'btn-warning text-dark' : 'btn-light border text-dark bg-white' }}" style="font-size: 0.82rem;" title="Urut LOS Terlama ke Baru">
                    <i class="mdi mdi-sort-numeric-descending me-1"></i>LOS Terlama
                </a>
                <a href="{{ route('maintenances.index', array_merge(request()->except(['sort', 'page']), ['sort' => 'los_singkat'])) }}"
                   class="btn btn-sm fw-bold {{ request('sort') === 'los_singkat' ? 'btn-info text-white' : 'btn-light border text-dark bg-white' }}" style="font-size: 0.82rem;" title="Urut LOS Singkat ke Lama">
                    <i class="mdi mdi-sort-numeric-ascending me-1"></i>LOS Singkat
                </a>
            </div>

            <!-- Per Page Dropdown -->
            <div class="input-group shadow-sm" style="width: auto;">
                <span class="input-group-text bg-white border-end-0 py-0" style="font-size: 0.85rem;"><i class="mdi mdi-format-list-numbered text-muted"></i></span>
                <select name="per_page" class="form-select border-start-0 ps-0 bg-white fw-bold text-dark" style="font-size: 0.88rem;" onchange="document.getElementById('monitoringFilterForm').submit();">
                    <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10 baris</option>
                    <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25 baris</option>
                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50 baris</option>
                    <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100 baris</option>
                </select>
            </div>

            <!-- Submit & Reset -->
            <button type="submit" class="btn btn-outline-secondary btn-sm shadow-sm" style="height: 38px; width: 38px;" title="Terapkan Filter">
                <i class="mdi mdi-magnify fs-5"></i>
            </button>
            <a href="{{ route('maintenances.index') }}" class="btn btn-light border bg-white shadow-sm d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Reset / Refresh">
                <i class="mdi mdi-refresh text-dark fs-4"></i>
            </a>
        </form>
    </div>
</div>
@stop

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center fw-bold w-100 shadow-sm mb-3" style="font-size: 1.1rem; border-radius: 8px;">
                <i class="mdi mdi-check-circle-outline fs-3 me-3 text-success"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show w-100 shadow-sm mb-3" style="border-radius: 8px;">
                <div class="d-flex align-items-center mb-2">
                    <i class="mdi mdi-alert-circle fs-3 me-2 text-danger"></i> <strong style="font-size: 1.1rem;">Gagal Menyimpan Data!</strong>
                </div>
                <ul class="mb-0 mt-1" style="font-size: 1.05rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Summary Cards Panel matching screenshot style --}}
        <div class="row g-3 mb-4">
            <!-- Total Pasien -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border border-light-subtle shadow-sm h-100" style="border-radius: 12px; background: #ffffff;">
                    <div class="card-body py-3 d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 52px; height: 52px; background-color: rgba(31, 59, 179, 0.1); flex-shrink: 0;">
                            <i class="mdi mdi-account-group text-primary fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small fw-bold text-uppercase">Total Pasien</p>
                            <h3 class="fw-bold mb-0 text-dark" style="font-size: 1.6rem; line-height: 1.2;">{{ $totalPasien }}</h3>
                            <small class="text-muted">pasien</small>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Pasien Baru -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border border-light-subtle shadow-sm h-100" style="border-radius: 12px; background: #ffffff;">
                    <div class="card-body py-3 d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 52px; height: 52px; background-color: rgba(25, 135, 84, 0.1); flex-shrink: 0;">
                            <i class="mdi mdi-calendar-plus text-success fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small fw-bold text-uppercase">Pasien Baru</p>
                            <h3 class="fw-bold mb-0 text-dark" style="font-size: 1.6rem; line-height: 1.2;">{{ $pasienBaru }}</h3>
                            <small class="text-muted">pasien</small>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Dalam Perawatan -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border border-light-subtle shadow-sm h-100" style="border-radius: 12px; background: #ffffff;">
                    <div class="card-body py-3 d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 52px; height: 52px; background-color: rgba(253, 126, 20, 0.1); flex-shrink: 0;">
                            <i class="mdi mdi-heart-pulse text-warning fs-3" style="color: #fd7e14 !important;"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small fw-bold text-uppercase">Dalam Perawatan</p>
                            <h3 class="fw-bold mb-0 text-dark" style="font-size: 1.6rem; line-height: 1.2;">{{ $dalamPerawatan }}</h3>
                            <small class="text-muted">pasien</small>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Siap Pulang -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border border-light-subtle shadow-sm h-100" style="border-radius: 12px; background: #ffffff;">
                    <div class="card-body py-3 d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 52px; height: 52px; background-color: rgba(111, 66, 193, 0.1); flex-shrink: 0;">
                            <i class="mdi mdi-calendar-check text-purple fs-3" style="color: #6f42c1 !important;"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small fw-bold text-uppercase">Siap Pulang</p>
                            <h3 class="fw-bold mb-0 text-dark" style="font-size: 1.6rem; line-height: 1.2;">{{ $siapPulang }}</h3>
                            <small class="text-muted">pasien</small>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Ada Barrier -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border border-light-subtle shadow-sm h-100" style="border-radius: 12px; background: #ffffff;">
                    <div class="card-body py-3 d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 52px; height: 52px; background-color: rgba(220, 53, 69, 0.1); flex-shrink: 0;">
                            <i class="mdi mdi-alert text-danger fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small fw-bold text-uppercase">Ada Barrier</p>
                            <h3 class="fw-bold mb-0 text-dark" style="font-size: 1.6rem; line-height: 1.2;">{{ $adaBarrier }}</h3>
                            <small class="text-muted">pasien</small>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Tanggal -->
            <div class="col-xl-2 col-md-4 col-sm-6">
                <div class="card border border-light-subtle shadow-sm h-100" style="border-radius: 12px; background: #ffffff;">
                    <div class="card-body py-3 d-flex align-items-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 52px; height: 52px; background-color: rgba(108, 117, 125, 0.1); flex-shrink: 0;">
                            <i class="mdi mdi-calendar-clock text-secondary fs-3"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0 small fw-bold text-uppercase">Tanggal</p>
                            <h5 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem; line-height: 1.2;">{{ now()->translatedFormat('d F Y') }}</h5>
                            <small class="text-muted">{{ now()->format('H:i') }} WIB</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive bg-white">
                    <table class="table table-hover table-striped align-middle mb-0" style="min-width: 1300px;">
                        <thead class="bg-light border-bottom text-dark">
                            <tr>
                                <th class="text-center py-3 fw-bold" style="width: 40px; font-size: 0.88rem; color: #4B5563;">No</th>
                                <th class="py-3 fw-bold" style="width: 250px; font-size: 0.88rem; color: #4B5563;">Nama Pasien<br><span class="text-muted fw-normal" style="font-size: 0.75rem;">No. RM | Jenis Kelamin | Umur<br>Diagnosa Medis</span></th>
                                <th class="py-3 fw-bold" style="width: 140px; font-size: 0.88rem; color: #4B5563;">Tgl Masuk</th>
                                <th class="py-3 fw-bold text-center" style="width: 90px; font-size: 0.88rem; color: #4B5563;">LOS</th>
                                <th class="py-3 fw-bold" style="width: 200px; font-size: 0.88rem; color: #4B5563;">DPJP<br><span class="text-muted fw-normal" style="font-size: 0.75rem;">Visite | Konsul (DPJP Konsul)</span></th>
                                <th class="py-3 fw-bold" style="width: 220px; font-size: 0.88rem; color: #4B5563;">Handover<br><span class="text-muted fw-normal" style="font-size: 0.75rem;">Pagi | Sore | Malam</span></th>
                                <th class="py-3 fw-bold" style="width: 220px; font-size: 0.88rem; color: #4B5563;">Planning Selama Perawatan<br><span class="text-muted fw-normal" style="font-size: 0.75rem;">Lab | Radiologi | Konsul | Tindakan | Dll</span></th>
                                <th class="py-3 fw-bold" style="width: 180px; font-size: 0.88rem; color: #4B5563;">Barrier</th>
                                <th class="py-3 fw-bold" style="width: 140px; font-size: 0.88rem; color: #4B5563;">Estimasi Pulang</th>
                                <th class="py-3 fw-bold text-center" style="width: 120px; font-size: 0.88rem; color: #4B5563;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($equipmentsPaginator as $key => $eq)
                            @php
                                $lastMaintenance = $eq->maintenances->first();
                                
                                // Tgl Masuk
                                $tglMasukRaw = $eq->registered_date ?: $eq->tanggal_pengadaan;
                                $tglMasukParsed = null;
                                try {
                                    $tglMasukParsed = \Carbon\Carbon::parse($tglMasukRaw);
                                } catch (\Exception $e) {}
                                $displayTglMasuk = $tglMasukParsed ? $tglMasukParsed->format('d/m/Y') : ($tglMasukRaw ?: '-');

                                $dayNamesIndonesian = [
                                    'Sunday' => 'Minggu',
                                    'Monday' => 'Senin',
                                    'Tuesday' => 'Selasa',
                                    'Wednesday' => 'Rabu',
                                    'Thursday' => 'Kamis',
                                    'Friday' => 'Jumat',
                                    'Saturday' => 'Sabtu'
                                ];
                                $displayHariMasuk = $tglMasukParsed ? '(' . ($dayNamesIndonesian[$tglMasukParsed->format('l')] ?? $tglMasukParsed->format('l')) . ')' : '';

                                // LOS Badge calculation (hari ini - tanggal masuk)
                                $losInt = 0;
                                $displayLos = '-';
                                if ($tglMasukParsed) {
                                    $losInt = (int)$tglMasukParsed->diffInDays(now()->startOfDay());
                                    $displayLos = $losInt . ' Hari';
                                }
                                $losClass = 'badge-los-green'; // 0-2 hari
                                if ($losInt >= 3 && $losInt <= 4) {
                                    $losClass = 'badge-los-orange'; // 3-4 hari
                                } elseif ($losInt > 4) {
                                    $losClass = 'badge-los-red'; // >4 hari
                                }

                                // Handover Parsing from spesifikasi
                                $handoverLines = array_filter(array_map('trim', explode("\n", $eq->spesifikasi ?? '')));
                                $pagiNote = '-';
                                $soreNote = '-';
                                $malamNote = '-';
                                foreach($handoverLines as $line) {
                                    if (stripos($line, 'pagi:') !== false || stripos($line, 'pagi -') !== false) {
                                        $pagiNote = trim(preg_replace('/^pagi\s*(:|-)\s*/i', '', $line));
                                    } elseif (stripos($line, 'sore:') !== false || stripos($line, 'sore -') !== false) {
                                        $soreNote = trim(preg_replace('/^sore\s*(:|-)\s*/i', '', $line));
                                    } elseif (stripos($line, 'malam:') !== false || stripos($line, 'malam -') !== false) {
                                        $malamNote = trim(preg_replace('/^malam\s*(:|-)\s*/i', '', $line));
                                    }
                                }
                                if ($pagiNote === '-' && $soreNote === '-' && $malamNote === '-' && !empty($eq->spesifikasi)) {
                                    $pagiNote = $handoverLines[0] ?? '-';
                                    $soreNote = $handoverLines[1] ?? '-';
                                    $malamNote = $handoverLines[2] ?? '-';
                                }

                                // Planning Checklist Parsing
                                $planningText = $eq->planning_pasien ?? '';
                                $planningItems = array_filter(array_map('trim', explode("\n", $planningText)));
                                $labCheck = false; $labDetail = '-';
                                $radCheck = false; $radDetail = '-';
                                $konCheck = false; $konDetail = '-';
                                $tndCheck = false; $tndDetail = '-';
                                $eduCheck = false; $eduDetail = '-';
                                foreach($planningItems as $item) {
                                    if (stripos($item, 'lab:') !== false || stripos($item, 'lab -') !== false) {
                                        $labCheck = true;
                                        $labDetail = trim(preg_replace('/^lab\s*(:|-)\s*/i', '', $item));
                                    } elseif (stripos($item, 'radiologi:') !== false || stripos($item, 'radiologi -') !== false) {
                                        $radCheck = true;
                                        $radDetail = trim(preg_replace('/^radiologi\s*(:|-)\s*/i', '', $item));
                                    } elseif (stripos($item, 'konsul:') !== false || stripos($item, 'konsul -') !== false) {
                                        $konCheck = true;
                                        $konDetail = trim(preg_replace('/^konsul\s*(:|-)\s*/i', '', $item));
                                    } elseif (stripos($item, 'tindakan:') !== false || stripos($item, 'tindakan -') !== false) {
                                        $tndCheck = true;
                                        $tndDetail = trim(preg_replace('/^tindakan\s*(:|-)\s*/i', '', $item));
                                    } elseif (stripos($item, 'edukasi:') !== false || stripos($item, 'edukasi -') !== false) {
                                        $eduCheck = true;
                                        $eduDetail = trim(preg_replace('/^edukasi\s*(:|-)\s*/i', '', $item));
                                    }
                                }
                                if (!$labCheck && !$radCheck && !$konCheck && !$tndCheck && !$eduCheck && !empty($planningText)) {
                                    $tndCheck = true;
                                    $tndDetail = $planningText;
                                }

                                // Estimasi Pulang
                                $tglPulangRaw = $eq->rencana_pulang ?: ($patientsMap[$eq->serial_number]['rencana_pulang'] ?? null);
                                if ($tglPulangRaw === '-') {
                                    $tglPulangRaw = null;
                                }
                                $tglPulangParsed = null;
                                if ($tglPulangRaw) {
                                    try {
                                        $tglPulangParsed = \Carbon\Carbon::parse($tglPulangRaw);
                                    } catch (\Exception $e) {}
                                }
                                $displayTglPulang = $tglPulangParsed ? $tglPulangParsed->format('d/m/Y') : ($tglPulangRaw ?: '');
                                $displayHariPulang = $tglPulangParsed ? '(' . ($dayNamesIndonesian[$tglPulangParsed->format('l')] ?? $tglPulangParsed->format('l')) . ')' : '';

                                 // Clean prefix tags like [v] or [ ] from Dokter Konsul string for dashboard display and parse checked status
                                 $rawDokterKonsul = $eq->dokter_konsul ?? '';
                                 $cleanDokterKonsul = '';
                                 $hasCheckedKonsul = false;
                                 if (!empty($rawDokterKonsul)) {
                                     $parts = explode(',', $rawDokterKonsul);
                                     $names = [];
                                     foreach ($parts as $part) {
                                         $part = trim($part);
                                         $checked = true; // default legacy fallback
                                         $name = $part;
                                         if (strpos($part, '[v] ') === 0) {
                                             $checked = true;
                                             $name = substr($part, 4);
                                         } elseif (strpos($part, '[ ] ') === 0) {
                                             $checked = false;
                                             $name = substr($part, 4);
                                         }
                                         if ($checked) {
                                             $hasCheckedKonsul = true;
                                         }
                                         $names[] = $name;
                                     }
                                     $cleanDokterKonsul = implode(', ', $names);
                                 }

                                 // EWS color coding
                                 $ewsColor = null;
                                 $ewsBg = '';
                                 $ewsText = '';
                                 if (!empty($eq->ews)) {
                                     $ewsLower = strtolower($eq->ews);
                                     if (stripos($ewsLower, 'hijau') !== false) {
                                         $ewsColor = '#198754';
                                         $ewsBg = '#e8f5e9';
                                         $ewsText = '#198754';
                                     } elseif (stripos($ewsLower, 'kuning') !== false) {
                                         $ewsColor = '#ffc107';
                                         $ewsBg = '#fff9c4';
                                         $ewsText = '#856404';
                                     } elseif (stripos($ewsLower, 'oranye') !== false || stripos($ewsLower, 'orange') !== false) {
                                         $ewsColor = '#fd7e14';
                                         $ewsBg = '#ffe0b2';
                                         $ewsText = '#d35400';
                                     } elseif (stripos($ewsLower, 'merah') !== false) {
                                         $ewsColor = '#dc3545';
                                         $ewsBg = '#ffebee';
                                         $ewsText = '#c62828';
                                     } elseif (stripos($ewsLower, 'dnr') !== false) {
                                         $ewsColor = '#6c757d';
                                         $ewsBg = '#f5f5f5';
                                         $ewsText = '#495057';
                                     }
                                 }
                            @endphp
                            <tr class="border-bottom">
                                <!-- No -->
                                <td class="text-center fw-bold text-dark" style="{{ $ewsColor ? 'border-left: 6px solid ' . $ewsColor . ' !important;' : '' }}">{{ $equipmentsPaginator->firstItem() + $key }}</td>
                                
                                <!-- Nama Pasien -->
                                <td>
                                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                        @if($ewsColor)
                                            <span class="d-inline-block rounded-circle shadow-sm" style="width: 12px; height: 12px; background-color: {{ $ewsColor }}; flex-shrink: 0;" title="EWS: {{ $eq->ews }}"></span>
                                        @endif
                                        <div class="fw-bold text-uppercase" style="font-size: 0.95rem; color: #0d6efd; line-height: 1.2;">
                                            {{ $eq->merk }}
                                        </div>
                                        @if($ewsColor)
                                            <span class="badge shadow-xs" style="background-color: {{ $ewsBg }}; color: {{ $ewsText }}; font-size: 0.72rem; padding: 2px 6px; border: 1px solid {{ $ewsColor }}; font-weight: bold; border-radius: 4px;">{{ $eq->ews }}</span>
                                        @endif
                                    </div>
                                    <div class="text-muted mb-1" style="font-size: 0.85rem; font-weight: 500;">
                                        RM. {{ $eq->serial_number }}
                                    </div>
                                    <div class="mb-1" style="font-size: 0.85rem; font-weight: 600; color: #dc3545;">
                                        <i class="mdi {{ $eq->gender == 'Laki-laki' || $eq->gender == 'Male' ? 'mdi-gender-male' : 'mdi-gender-female' }} me-1"></i>
                                        {{ $eq->gender == 'Laki-laki' || $eq->gender == 'Male' ? 'Laki-laki' : 'Perempuan' }} | {{ $eq->tanggal_lahir ? \Carbon\Carbon::parse($eq->tanggal_lahir)->age : '-' }} Th
                                    </div>
                                    <div class="text-dark fw-bold mb-2" style="font-size: 0.85rem;">
                                        {{ $eq->type }}
                                    </div>

                                    <!-- Tiny interactive toggle buttons for Lab, Rad, and Obat -->
                                    <div class="d-flex flex-wrap gap-1 mt-1 mb-2">
                                        @if(!empty($eq->riw_lab))
                                            <button class="btn btn-outline-primary btn-xs py-0 px-2 fw-bold" style="font-size: 0.72rem; border-radius: 4px;" type="button" data-bs-toggle="collapse" data-bs-target="#riw_lab_collapse_{{ $eq->id }}" aria-expanded="false">
                                                <i class="mdi mdi-flask-outline me-0.5"></i> Lab
                                            </button>
                                        @endif
                                        @if(!empty($eq->riw_rad))
                                            <button class="btn btn-outline-success btn-xs py-0 px-2 fw-bold" style="font-size: 0.72rem; border-radius: 4px;" type="button" data-bs-toggle="collapse" data-bs-target="#riw_rad_collapse_{{ $eq->id }}" aria-expanded="false">
                                                <i class="mdi mdi-video-outline me-0.5"></i> Rad
                                            </button>
                                        @endif
                                        @if(!empty($eq->riw_obat))
                                            <button class="btn btn-outline-danger btn-xs py-0 px-2 fw-bold" style="font-size: 0.72rem; border-radius: 4px;" type="button" data-bs-toggle="collapse" data-bs-target="#riw_obat_collapse_{{ $eq->id }}" aria-expanded="false">
                                                <i class="mdi mdi-pill me-0.5"></i> Obat
                                            </button>
                                        @endif
                                    </div>

                                    <!-- Collapsible containers -->
                                    @if(!empty($eq->riw_lab))
                                        <div class="collapse mt-1" id="riw_lab_collapse_{{ $eq->id }}">
                                            <div class="card card-body p-2 bg-light border-primary" style="font-size: 0.75rem; font-weight: 600; line-height: 1.3;">
                                                <strong class="text-primary"><i class="mdi mdi-flask-outline"></i> Riw Lab:</strong>
                                                {{ $eq->riw_lab }}
                                            </div>
                                        </div>
                                    @endif
                                    @if(!empty($eq->riw_rad))
                                        <div class="collapse mt-1" id="riw_rad_collapse_{{ $eq->id }}">
                                            <div class="card card-body p-2 bg-light border-success" style="font-size: 0.75rem; font-weight: 600; line-height: 1.3;">
                                                <strong class="text-success"><i class="mdi mdi-video-outline"></i> Riw Rad:</strong>
                                                {{ $eq->riw_rad }}
                                            </div>
                                        </div>
                                    @endif
                                    @if(!empty($eq->riw_obat))
                                        <div class="collapse mt-1" id="riw_obat_collapse_{{ $eq->id }}">
                                            <div class="card card-body p-2 bg-light border-danger" style="font-size: 0.75rem; font-weight: 600; line-height: 1.3;">
                                                <strong class="text-danger"><i class="mdi mdi-pill"></i> Riw Obat:</strong>
                                                {{ $eq->riw_obat }}
                                            </div>
                                        </div>
                                    @endif
                                </td>

                                <!-- Tgl Masuk -->
                                <td>
                                    @if($tglMasukRaw)
                                        <div class="d-flex align-items-center text-dark" style="font-size: 0.88rem; font-weight: 500;">
                                            <i class="mdi mdi-calendar-text text-muted me-1.5 fs-5"></i>
                                            <div>
                                                {{ $displayTglMasuk }}<br>
                                                <span class="text-muted small">{{ $displayHariMasuk }}</span>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <!-- LOS -->
                                <td class="text-center">
                                    <span class="badge {{ $losClass }} px-3 py-1.5" style="font-size: 0.88rem; border-radius: 20px; font-weight: bold; border: 1px solid;">
                                        {{ $displayLos }}
                                    </span>
                                </td>

                                <!-- DPJP -->
                                <td>
                                    <div style="font-size: 0.88rem; line-height: 1.4;">
                                        <div class="fw-bold text-dark mb-1">{{ $eq->dpjp_utama ?: '-' }}</div>
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="text-muted me-2" style="font-size: 0.8rem;">Visite</span>
                                            <i class="mdi {{ !empty($eq->visit_dpjp) && (stripos($eq->visit_dpjp, 'tidak') === false) && (stripos($eq->visit_dpjp, 'belum') === false) ? 'mdi-checkbox-marked text-success' : 'mdi-checkbox-blank-outline text-muted' }}" style="font-size: 1.1rem;"></i>
                                        </div>
                                        <div class="fw-bold text-dark mb-1">{{ $cleanDokterKonsul ?: '-' }}</div>
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="text-muted me-2" style="font-size: 0.8rem;">Konsul</span>
                                            <i class="mdi {{ $hasCheckedKonsul ? 'mdi-checkbox-marked text-success' : 'mdi-checkbox-blank-outline text-muted' }}" style="font-size: 1.1rem;"></i>
                                        </div>
                                    </div>
                                </td>

                                 <!-- Handover -->
                                 <td>
                                     <div style="font-size: 0.85rem; line-height: 1.45;">
                                         <div class="mb-1">
                                             <span class="text-muted fw-bold">Pagi:</span> <span class="text-dark fw-bold">{{ $pagiNote }}</span>
                                             <div class="text-muted small ps-2" style="font-size: 0.75rem;">Ners: <span class="fw-bold text-dark">{{ $eq->ners_pagi ?: '-' }}</span></div>
                                         </div>
                                         <div class="mb-1">
                                             <span class="text-muted fw-bold">Sore:</span> <span class="text-dark fw-bold">{{ $soreNote }}</span>
                                             <div class="text-muted small ps-2" style="font-size: 0.75rem;">Ners: <span class="fw-bold text-dark">{{ $eq->ners_siang ?: '-' }}</span></div>
                                         </div>
                                         <div>
                                             <span class="text-muted fw-bold">Malam:</span> <span class="text-dark fw-bold">{{ $malamNote }}</span>
                                             <div class="text-muted small ps-2" style="font-size: 0.75rem;">Ners: <span class="fw-bold text-dark">{{ $eq->ners_malam ?: '-' }}</span></div>
                                         </div>
                                     </div>
                                 </td>

                                <!-- Planning Selama Perawatan -->
                                <td>
                                    <div style="font-size: 0.82rem; line-height: 1.4;">
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="mdi {{ $labCheck ? 'mdi-checkbox-marked text-success' : 'mdi-checkbox-blank-outline text-muted' }} me-1.5" style="font-size: 1.1rem;"></i>
                                            <span class="text-dark">Lab: <span class="fw-bold">{{ $labDetail }}</span></span>
                                        </div>
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="mdi {{ $radCheck ? 'mdi-checkbox-marked text-success' : 'mdi-checkbox-blank-outline text-muted' }} me-1.5" style="font-size: 1.1rem;"></i>
                                            <span class="text-dark">Radiologi: <span class="fw-bold">{{ $radDetail }}</span></span>
                                        </div>
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="mdi {{ $konCheck ? 'mdi-checkbox-marked text-success' : 'mdi-checkbox-blank-outline text-muted' }} me-1.5" style="font-size: 1.1rem;"></i>
                                            <span class="text-dark">Konsul: <span class="fw-bold">{{ $konDetail }}</span></span>
                                        </div>
                                        <div class="d-flex align-items-center mb-1">
                                            <i class="mdi {{ $tndCheck ? 'mdi-checkbox-marked text-success' : 'mdi-checkbox-blank-outline text-muted' }} me-1.5" style="font-size: 1.1rem;"></i>
                                            <span class="text-dark">Tindakan: <span class="fw-bold">{{ $tndDetail }}</span></span>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <i class="mdi {{ $eduCheck ? 'mdi-checkbox-marked text-success' : 'mdi-checkbox-blank-outline text-muted' }} me-1.5" style="font-size: 1.1rem;"></i>
                                            <span class="text-dark">Edukasi/Dll: <span class="fw-bold">{{ $eduDetail }}</span></span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Barrier -->
                                <td>
                                    <div class="text-dark" style="font-size: 0.88rem; font-weight: 600; line-height: 1.4;">
                                        {{ $eq->alkes_invasif ?: '-' }}
                                    </div>
                                </td>

                                <!-- Estimasi Pulang -->
                                <td>
                                    @if($tglPulangRaw && $tglPulangRaw !== '-')
                                        <div class="d-flex align-items-center text-dark" style="font-size: 0.88rem; font-weight: 500;">
                                            <i class="mdi mdi-calendar text-muted me-1.5 fs-5"></i>
                                            <div>
                                                {{ $displayTglPulang }}<br>
                                                <span class="text-muted small">{{ $displayHariPulang }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </td>

                                <!-- Aksi Dropdown input/edit matching screenshot -->
                                <td class="text-center">
                                    <div class="dropdown">
                                        <button class="btn btn-outline-primary btn-sm dropdown-toggle fw-bold py-1.5 px-3 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.85rem; border-radius: 8px;">
                                            Input / Edit
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius: 12px; min-width: 150px;">
                                            <li>
                                                <a class="dropdown-item fw-bold d-flex align-items-center py-2 px-3 text-info" href="{{ route('maintenances.patient_detail', $eq->serial_number) }}">
                                                    <i class="mdi mdi-account-card-details me-2 fs-5"></i> Detail Pasien
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item fw-bold d-flex align-items-center py-2 px-3 text-primary" href="{{ route('maintenances.history', $eq->serial_number) }}">
                                                    <i class="mdi mdi-history me-2 fs-5"></i> Lihat Riwayat
                                                </a>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider my-1">
                                            </li>
                                            <li>
                                                <button class="dropdown-item fw-bold d-flex align-items-center py-2 px-3 text-dark border-0 bg-transparent" type="button" data-bs-toggle="modal" data-bs-target="#editEquipmentModal{{ $eq->id }}">
                                                    <i class="mdi mdi-pencil-outline me-2 fs-5"></i> Edit Profil
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <i class="mdi mdi-text-box-search-outline text-muted" style="font-size: 4rem;"></i>
                                    <h4 class="mt-3 text-dark fw-bold">Data Pasien Tidak Ditemukan</h4>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Table footer, legends, and refresh stats --}}
                <div class="card-footer bg-light px-4 py-3 border-0 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                    <!-- Legend mapping -->
                    <div class="d-flex align-items-center gap-3 flex-wrap text-muted" style="font-size: 0.85rem; font-weight: bold;">
                        <span>Keterangan LOS:</span>
                        <div class="d-flex align-items-center">
                            <span class="rounded-circle d-inline-block me-1.5" style="width: 12px; height: 12px; background-color: #198754;"></span>
                            <span>0 - 2 hari</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="rounded-circle d-inline-block me-1.5" style="width: 12px; height: 12px; background-color: #fd7e14;"></span>
                            <span>3 - 4 hari</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="rounded-circle d-inline-block me-1.5" style="width: 12px; height: 12px; background-color: #dc3545;"></span>
                            <span>> 4 hari</span>
                        </div>
                    </div>
                    <!-- Update Timestamp -->
                    <div class="text-muted d-flex align-items-center gap-2" style="font-size: 0.85rem; font-weight: 500;">
                        <span>Update terakhir: {{ now()->translatedFormat('d F Y H:i') }} WIB</span>
                        <a href="{{ route('maintenances.index') }}" class="text-decoration-none text-primary"><i class="mdi mdi-refresh"></i></a>
                    </div>
                </div>

                <div class="mt-4 pt-3 d-flex justify-content-center">
                    {{ $equipmentsPaginator->appends(request()->input())->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Laporan Kunjungan/Riwayat -->
<div class="modal fade" id="addMaintenanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header bg-danger px-4 py-3 text-white">
                <h5 class="modal-title fw-bold text-white fs-4"><i class="mdi mdi-plus-box-multiple-outline me-2"></i> Tulis Riwayat Pasien Baru</h5>
                <button type="button" class="btn-close btn-close-white text-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('maintenances.store') }}" method="POST">
                @csrf
                <div class="modal-body px-4 py-4 bg-light">
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Pasien Target <span class="text-danger">*</span></label>
                            <select name="equipment_id" class="form-select form-select-lg bg-white fw-bold text-dark" required>
                                <option value="" disabled selected>-- Pilih Pasien --</option>
                                @foreach($equipments as $eq)
                                    <option value="{{ $eq->id }}"
                                        data-diagnosis="{{ $eq->type }}"
                                        data-lokasi="{{ $eq->lokasi }}"
                                        data-kondisi="{{ $eq->kondisi }}"
                                        data-pembayaran="{{ $eq->status_kepemilikan }}">
                                        {{ $eq->merk }} (No. RM: {{ $eq->serial_number }})@if($eq->lantai) - Lantai {{ $eq->lantai }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Kategori Tindakan <span class="text-danger">*</span></label>
                            <select name="jenis_pemeliharaan" class="form-select form-select-lg bg-white fw-bold text-dark" required>
                                <option value="Preventif">Rutin / Kontrol (Pencegahan)</option>
                                <option value="Pemindahan Poli">Rujukan / Pindah Poli</option>
                                <option value="Korektif">Darurat / UGD (Tindakan Medis)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Dokter / Tenaga Medis <span class="text-danger">*</span></label>
                            <input type="text" name="petugas" class="form-control form-control-lg bg-white" required placeholder="Sebutkan Nama Dokter / Perawat" list="doctors_list">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Tanggal Pemeriksaan / Tindakan <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_pelaksanaan" class="form-control form-control-lg bg-white" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Rencana Kontrol Selanjutnya <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_jadwal_berikutnya" class="form-control form-control-lg bg-white border-danger fw-bold" required>
                            <small class="text-muted fw-bold mt-1 d-block"><i class="mdi mdi-alert-circle text-danger me-1"></i> Mengatur jadwal janji temu kontrol berikutnya.</small>
                        </div>

                        <!-- 4 New Clinical and Billing Fields -->
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Diagnosa Utama / Gejala Saat Ini</label>
                            <input type="text" name="diagnosa_gejala" id="add_diagnosa_gejala" class="form-control form-control-lg bg-white" placeholder="Contoh: Infeksi Saluran Pernapasan">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Ruang Rawat / Lokasi Saat Ini</label>
                            <input type="text" name="lokasi_rawat" id="add_lokasi_rawat" class="form-control form-control-lg bg-white" placeholder="Contoh: Poli Dalam / Gedung A">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Status Kondisi Klinis Saat Ini</label>
                            <select name="kondisi_klinis" id="add_kondisi_klinis" class="form-select form-select-lg bg-white fw-bold text-dark">
                                <option value="Stabil EWS">Stabil EWS (Hijau)</option>
                                <option value="Stabil perlu observasi rutin EWS">Stabil perlu observasi rutin EWS (Kuning)</option>
                                <option value="Perlu pemantauan khusus EWS">Perlu pemantauan khusus EWS (Kuning)</option>
                                <option value="Perlu pemantauan ketat EWS">Perlu pemantauan ketat EWS (Orange)</option>
                                <option value="Intensif ESW">Intensif ESW (Merah)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Metode Pembayaran Saat Ini</label>
                            <select name="metode_pembayaran" id="add_metode_pembayaran" class="form-select form-select-lg bg-white fw-bold text-dark">
                                <option value="Milik RS">BPJS Kesehatan</option>
                                <option value="KSO">Asuransi Swasta</option>
                                <option value="Hibah">Umum / Mandiri</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-2">
                            <label class="form-label text-dark fw-bold fs-5">Catatan Handover <span class="text-danger">*</span></label>
                            <textarea name="tindakan_hasil" class="form-control bg-white" rows="4" required placeholder="Pemeriksaan fisik menunjukkan gejala flu, memberikan resep obat paracetamol, pasien disarankan istirahat."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white px-4 py-3">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">TUNDA</button>
                    <button type="submit" class="btn btn-danger text-white fw-bold px-4"><i class="mdi mdi-content-save me-1"></i> SIMPAN RIWAYAT</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL REGISTRASI PASIEN BARU --}}
<div class="modal fade" id="addEquipmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header bg-primary px-4 py-3 text-white">
                <h5 class="modal-title fw-bold text-white fs-4"><i class="mdi mdi-account-plus me-2"></i> Registrasi Pasien Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('equipments.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body px-4 py-4 bg-light">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Nama Lengkap Pasien <span class="text-danger">*</span></label>
                            <input type="text" name="merk" class="form-control form-control-lg bg-white" required placeholder="Contoh: Budi Santoso">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">No. Rekam Medis (RM) <span class="text-danger">*</span></label>
                            <input type="text" name="serial_number" class="form-control form-control-lg bg-white border-primary" required placeholder="Nomor Rekam Medis Pasien">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Tanggal Lahir Pasien <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_lahir" class="form-control form-control-lg bg-white" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Ruang Rawat / Lokasi <span class="text-danger">*</span></label>
                            <input type="text" name="lokasi" class="form-control form-control-lg bg-white" required placeholder="Contoh: Ruang Melati / Poliklinik">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Lantai <span class="text-danger">*</span></label>
                            <select name="lantai" class="form-select form-select-lg bg-white fw-bold text-dark" required>
                                <option value="" disabled selected>-- Pilih Lantai --</option>
                                @foreach($globalFloors as $fl)
                                    @php
                                        $flName = $fl->name;
                                        $displayFl = is_numeric($flName) ? 'Lantai ' . $flName : $flName;
                                    @endphp
                                    <option value="{{ $flName }}">{{ $displayFl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select name="status_kepemilikan" class="form-select form-select-lg bg-white fw-bold text-dark" required>
                                <option value="Milik RS">BPJS Kesehatan</option>
                                <option value="KSO">Asuransi Swasta</option>
                                <option value="Hibah">Umum / Mandiri</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Tanggal Registrasi <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_pengadaan" class="form-control form-control-lg bg-white" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Jam Handover <span class="text-danger">*</span></label>
                            <input type="time" name="jam" class="form-control form-control-lg bg-white" required value="{{ date('H:i') }}">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Kondisi Saat Ini <span class="text-danger">*</span></label>
                            <select name="kondisi" class="form-select form-select-lg bg-white fw-bold text-dark" required>
                                <option value="Stabil EWS">Stabil EWS (Hijau)</option>
                                <option value="Stabil perlu observasi rutin EWS">Stabil perlu observasi rutin EWS (Kuning)</option>
                                <option value="Perlu pemantauan khusus EWS">Perlu pemantauan khusus EWS (Kuning)</option>
                                <option value="Perlu pemantauan ketat EWS">Perlu pemantauan ketat EWS (Orange)</option>
                                <option value="Intensif ESW">Intensif ESW (Merah)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Upload Foto / Identitas Pasien</label>
                            <input type="file" name="gambar" class="form-control form-control-lg bg-white" accept="image/*">
                        </div>
                        <div class="col-md-12 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Diagnosa Utama / Gejala <span class="text-danger">*</span></label>
                            <input type="text" name="type" class="form-control form-control-lg bg-white" required placeholder="Contoh: Demam Tinggi / Hipertensi">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-dark fw-bold fs-5">Catatan Handover (Opsional)</label>
                            <textarea name="spesifikasi" class="form-control bg-white" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white px-4 py-3">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4"><i class="mdi mdi-content-save me-1"></i> SIMPAN KE DATABASE</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODALS EDIT & HAPUS PASIEN (EQUIPMENT) --}}
@foreach($equipmentsPaginator as $eq)
    {{-- MODAL EDIT PASIEN --}}
    <div class="modal fade" id="editEquipmentModal{{ $eq->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px;">
                <div class="modal-header bg-dark px-4 py-3 text-white">
                    <h5 class="modal-title fw-bold text-white fs-4"><i class="mdi mdi-pencil-box-outline me-2"></i> Perbarui Profil Pasien</h5>
                    <button type="button" class="btn-close btn-close-white text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('equipments.update', $eq->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-body px-4 py-4 bg-light">
                        <div class="row">
                            <!-- Demographics -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark fw-bold">Nama Lengkap Pasien <span class="text-danger">*</span></label>
                                <input type="text" name="merk" class="form-control form-control-lg fw-bold" value="{{ $eq->merk }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark fw-bold">No. Rekam Medis (RM) <span class="text-danger">*</span></label>
                                <input type="text" name="serial_number" class="form-control form-control-lg border-primary fw-bold" value="{{ $eq->serial_number }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-dark fw-bold">Tanggal Lahir <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_lahir" class="form-control" value="{{ $eq->tanggal_lahir }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-dark fw-bold">Jenis Kelamin</label>
                                <select name="gender" class="form-select fw-bold text-dark">
                                    <option value="Laki-laki" {{ $eq->gender == 'Laki-laki' || $eq->gender == 'Male' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="Perempuan" {{ $eq->gender == 'Perempuan' || $eq->gender == 'Female' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-dark fw-bold">Penjamin / Guarantor</label>
                                <input type="text" name="guarantor" class="form-control" value="{{ $eq->guarantor }}" placeholder="Contoh: BPJS / Mandiri">
                            </div>

                            <!-- Ward Info -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-dark fw-bold">Ruang Rawat / Lokasi <span class="text-danger">*</span></label>
                                <input type="text" name="lokasi" class="form-control fw-bold text-primary" value="{{ $eq->lokasi }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-dark fw-bold">Lantai <span class="text-danger">*</span></label>
                                <select name="lantai" class="form-select fw-bold text-dark" required>
                                    <option value="" disabled>-- Pilih Lantai --</option>
                                    @foreach($globalFloors as $fl)
                                        @php
                                            $flName = $fl->name;
                                            $displayFl = is_numeric($flName) ? 'Lantai ' . $flName : $flName;
                                        @endphp
                                        <option value="{{ $flName }}" {{ $eq->lantai == $flName ? 'selected' : '' }}>{{ $displayFl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-dark fw-bold">Metode Pembayaran</label>
                                <select name="status_kepemilikan" class="form-select fw-bold text-dark" required>
                                    <option value="Milik RS" {{ $eq->status_kepemilikan == 'Milik RS' ? 'selected' : '' }}>BPJS Kesehatan</option>
                                    <option value="KSO" {{ $eq->status_kepemilikan == 'KSO' ? 'selected' : '' }}>Asuransi Swasta</option>
                                    <option value="Hibah" {{ $eq->status_kepemilikan == 'Hibah' ? 'selected' : '' }}>Umum / Mandiri</option>
                                </select>
                            </div>

                            <!-- Basic Register Details -->
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-dark fw-bold">Tanggal Registrasi</label>
                                <input type="date" name="tanggal_pengadaan" class="form-control" value="{{ $eq->tanggal_pengadaan }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-dark fw-bold">Jam Handover</label>
                                <input type="time" name="jam" class="form-control" value="{{ $eq->jam }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-dark fw-bold">Kondisi Saat Ini</label>
                                <select name="kondisi" class="form-select fw-bold text-dark" required>
                                    <option value="Stabil EWS" {{ $eq->kondisi == 'Stabil EWS' || $eq->kondisi == 'Baik' ? 'selected' : '' }}>Stabil EWS (Hijau)</option>
                                    <option value="Stabil perlu observasi rutin EWS" {{ $eq->kondisi == 'Stabil perlu observasi rutin EWS' ? 'selected' : '' }}>Stabil perlu observasi rutin EWS (Kuning)</option>
                                    <option value="Perlu pemantauan khusus EWS" {{ $eq->kondisi == 'Perlu pemantauan khusus EWS' || $eq->kondisi == 'Rusak Ringan' ? 'selected' : '' }}>Perlu pemantauan khusus EWS (Kuning)</option>
                                    <option value="Perlu pemantauan ketat EWS" {{ $eq->kondisi == 'Perlu pemantauan ketat EWS' ? 'selected' : '' }}>Perlu pemantauan ketat EWS (Orange)</option>
                                    <option value="Intensif ESW" {{ $eq->kondisi == 'Intensif ESW' || $eq->kondisi == 'Intensif EWS' || $eq->kondisi == 'Rusak Berat' ? 'selected' : '' }}>Intensif ESW (Merah)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark fw-bold">Foto Profil Pasien</label>
                                <input type="file" name="gambar" class="form-control" accept="image/*">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark fw-bold">Diagnosa Medis / Gejala <span class="text-danger">*</span></label>
                                <input type="text" name="type" class="form-control fw-bold" value="{{ $eq->type }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white px-4 py-3">
                        <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">BATAL</button>
                        <button type="submit" class="btn btn-dark fw-bold px-4"><i class="mdi mdi-content-save me-1"></i> SIMPAN PERUBAHAN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Dropdown autofill for addMaintenanceModal
        const patientSelect = document.querySelector('#addMaintenanceModal select[name="equipment_id"]');
        
        function populateFields() {
            if (!patientSelect) return;
            const selectedOption = patientSelect.options[patientSelect.selectedIndex];
            if (!selectedOption) return;
            const diagnosis = selectedOption.getAttribute('data-diagnosis') || '';
            const lokasi = selectedOption.getAttribute('data-lokasi') || '';
            const kondisi = selectedOption.getAttribute('data-kondisi') || 'Baik';
            const pembayaran = selectedOption.getAttribute('data-pembayaran') || 'Milik RS';

            document.getElementById('add_diagnosa_gejala').value = diagnosis;
            document.getElementById('add_lokasi_rawat').value = lokasi;
            document.getElementById('add_kondisi_klinis').value = kondisi;
            document.getElementById('add_metode_pembayaran').value = pembayaran;
        }

        if (patientSelect) {
            patientSelect.addEventListener('change', populateFields);
            // Trigger populate on load if patient is already selected
            if (patientSelect.selectedIndex > 0) {
                populateFields();
            }
        }

        // Auto-open addEquipmentModal if ?register=1 query param is present
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('register') === '1') {
            const addEquipmentModal = document.getElementById('addEquipmentModal');
            if (addEquipmentModal) {
                var modal = new bootstrap.Modal(addEquipmentModal);
                modal.show();

                // Prefill fields from query parameters if modal is opened
                const lantaiParam = urlParams.get('lantai') || urlParams.get('floor');
                const wingParam = urlParams.get('wing');
                const roomParam = urlParams.get('room');

                if (lantaiParam) {
                    const selectLantai = addEquipmentModal.querySelector('select[name="lantai"]');
                    if (selectLantai) {
                        let rawLantai = lantaiParam;
                        if (rawLantai.toLowerCase().startsWith('lantai ')) {
                            rawLantai = rawLantai.substring(7);
                        }
                        for (let option of selectLantai.options) {
                            if (option.value === rawLantai || option.value === lantaiParam) {
                                option.selected = true;
                                break;
                            }
                        }
                    }
                }

                if (roomParam) {
                    const inputLokasi = addEquipmentModal.querySelector('input[name="lokasi"]');
                    if (inputLokasi) {
                        let lokasiVal = roomParam;
                        if (wingParam) {
                            lokasiVal = wingParam + ' - ' + roomParam;
                        }
                        inputLokasi.value = lokasiVal;
                    }
                }
            }
        }
    });
</script>

<style>
    /* Styling elements to match screenshot exactly */
    .badge-los-green {
        background-color: #e8f5e9 !important;
        color: #198754 !important;
        border-color: #198754 !important;
    }
    .badge-los-orange {
        background-color: #fff3e0 !important;
        color: #ef6c00 !important;
        border-color: #ef6c00 !important;
    }
    .badge-los-red {
        background-color: #ffebee !important;
        color: #c62828 !important;
        border-color: #c62828 !important;
    }
    .table td, .table th {
        padding: 12px 10px !important;
        vertical-align: middle !important;
    }
    .dropdown-menu-item:hover {
        background-color: #f8f9fa !important;
    }
</style>
@php
    $doctorsList = \App\Models\Doctor::orderBy('name')->get();
@endphp
<datalist id="doctors_list">
    @foreach($doctorsList as $doc)
        <option value="{{ $doc->name }}">{{ $doc->ksm }}</option>
    @endforeach
</datalist>
@stop
