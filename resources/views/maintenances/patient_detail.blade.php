@extends('layouts.staradmin')

@section('title', 'Detail Pasien')

@section('content_header')
<div class="row align-items-center mb-4">
    <div class="col-sm-6">
        <h2 class="h2 text-dark font-weight-bold mb-1">
            <i class="mdi mdi-account-card-details text-primary me-2"></i> Detail Informasi Klinis Pasien
        </h2>
        <p class="text-muted mb-0" style="font-size: 1.05rem;">
            Pasien: <strong class="text-primary">{{ $equipment->merk }}</strong> (No. RM: {{ $equipment->serial_number }})
        </p>
    </div>
    <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
        <a href="{{ route('maintenances.index') }}" class="btn btn-outline-dark fw-bold px-4 py-2 shadow-sm" style="font-size: 1rem;">
            <i class="mdi mdi-arrow-left me-1 fs-5"></i> Kembali ke Daftar
        </a>
    </div>
</div>
@stop

@section('content')
<div class="row">
    @if(session('success'))
        <div class="col-lg-12">
            <div class="alert alert-success d-flex align-items-center fw-bold w-100 shadow-sm mb-4" style="font-size: 1.1rem;">
                <i class="mdi mdi-check-circle-outline fs-3 me-3"></i> {{ session('success') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    <!-- Kolom Info Pasien (API Data) -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm border-0" style="border-top: 4px solid #007bff !important;">
            <div class="card-body">
                <h4 class="card-title fw-bold text-primary mb-3">INFORMASI DASAR (API)</h4>
                <div class="text-center py-3">
                    <i class="mdi mdi-account-circle text-muted" style="font-size: 5rem;"></i>
                    <h4 class="fw-bold text-dark mt-2 mb-1">{{ $equipment->merk }}</h4>
                    <span class="badge bg-light text-dark border px-3 py-2 fw-bold"><i class="mdi mdi-barcode me-1"></i> No. RM: {{ $equipment->serial_number }}</span>
                </div>
                <hr>
                <div class="profile-feed">
                    <div class="d-flex align-items-center py-2 border-bottom">
                        <i class="mdi mdi-gender-male-female text-primary fs-4 me-3"></i>
                        <div>
                            <p class="text-muted mb-0 small">Jenis Kelamin</p>
                            <h5 class="fw-bold mb-0 text-dark">{{ $apiData['gender'] ?? ($equipment->gender ?: '-') }}</h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-center py-2 border-bottom">
                        <i class="mdi mdi-shield-outline text-success fs-4 me-3"></i>
                        <div>
                            <p class="text-muted mb-0 small">Guarantor / Penjamin</p>
                            <h5 class="fw-bold mb-0 text-dark">{{ $apiData['guarantor'] ?? ($equipment->guarantor ?: '-') }}</h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-center py-2 border-bottom">
                        <i class="mdi mdi-hotel text-info fs-4 me-3"></i>
                        <div>
                            <p class="text-muted mb-0 small">Hak Kelas Bed</p>
                            <h5 class="fw-bold mb-0 text-dark">{{ $apiData['class'] ?? ($equipment->hak_kelas ?: '-') }}</h5>
                        </div>
                    </div>
                    <div class="d-flex align-items-center py-2 border-bottom">
                        <i class="mdi mdi-map-marker text-danger fs-4 me-3"></i>
                        <div>
                            <p class="text-muted mb-0 small">Lokasi Rawat Sekarang</p>
                            <h5 class="fw-bold mb-0 text-dark">{{ $equipment->lokasi ?: '-' }}</h5>
                        </div>
                    </div>
                    @if($equipment->lantai)
                    <div class="d-flex align-items-center py-2">
                        <i class="mdi mdi-layers-outline text-warning fs-4 me-3"></i>
                        <div>
                            <p class="text-muted mb-0 small">Lantai</p>
                            <h5 class="fw-bold mb-0 text-dark">Lantai {{ $equipment->lantai }}</h5>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Detail Medis Klinis (Manual Freetext) -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm border-0" style="border-top: 4px solid #DC3545 !important;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title fw-bold text-danger mb-0">INFORMASI MEDIS KLINIS & PEMANTAUAN</h4>
                </div>

                <!-- EDIT FORM MODE -->
                <div id="editMode">
                    <form action="{{ route('maintenances.update_patient_detail', $equipment->serial_number) }}" method="POST">
                        @csrf
                        @method('PUT')
                        @php
                            // Demographics and entrance times
                            $tglMasukRaw = $equipment->registered_date ?: $equipment->tanggal_pengadaan;
                            $tglMasukParsed = null;
                            try {
                                $tglMasukParsed = \Carbon\Carbon::parse($tglMasukRaw);
                            } catch (\Exception $e) {}
                            
                            $dayNamesIndonesian = [
                                'Sunday' => 'Minggu',
                                'Monday' => 'Senin',
                                'Tuesday' => 'Selasa',
                                'Wednesday' => 'Rabu',
                                'Thursday' => 'Kamis',
                                'Friday' => 'Jumat',
                                'Saturday' => 'Sabtu'
                            ];
                            $displayHariMasuk = $tglMasukParsed ? ' (' . ($dayNamesIndonesian[$tglMasukParsed->format('l')] ?? $tglMasukParsed->format('l')) . ')' : '';
                            $displayTglMasuk = $tglMasukParsed ? $tglMasukParsed->format('d/m/Y') . $displayHariMasuk : ($tglMasukRaw ?: '-');

                            $dynamicLos = '-';
                            $losIntVal = 0;
                            if ($tglMasukParsed) {
                                $losIntVal = (int)$tglMasukParsed->diffInDays(now()->startOfDay());
                                $dynamicLos = $losIntVal . ' Hari';
                            }
                            
                            $targetLosRaw = $equipment->target_los;
                            $targetLosInt = (int)preg_replace('/[^0-9]/', '', $targetLosRaw);
                            $isOverLos = false;
                            if ($targetLosInt > 0 && $losIntVal > $targetLosInt) {
                                $isOverLos = true;
                            }

                            // Dokter Konsul parsing
                            $rawDokterKonsul = $equipment->dokter_konsul ?? '';
                            $konsulHistoryMap = [];
                            if ($equipment->konsul_history) {
                                $konsulHistoryMap = json_decode($equipment->konsul_history, true) ?: [];
                            }
                            $konsulDoctors = [];
                            if (!empty($rawDokterKonsul)) {
                                $parts = explode(',', $rawDokterKonsul);
                                foreach ($parts as $part) {
                                    $part = trim($part);
                                    if ($part === '') continue;
                                    
                                    $checked = true;
                                    $name = $part;
                                    if (strpos($part, '[v] ') === 0) {
                                        $checked = true;
                                        $name = substr($part, 4);
                                    } elseif (strpos($part, '[ ] ') === 0) {
                                        $checked = false;
                                        $name = substr($part, 4);
                                    }
                                    
                                    // Get last visit time for this consult doctor
                                    $docLastVisit = '-';
                                    if (isset($konsulHistoryMap[$name]) && !empty($konsulHistoryMap[$name])) {
                                        $docTimestamps = $konsulHistoryMap[$name];
                                        $lastTs = end($docTimestamps);
                                        try {
                                            $docLastVisit = \Carbon\Carbon::parse($lastTs)->format('d/m H:i');
                                        } catch (\Exception $e) {}
                                    }
                                    
                                    $konsulDoctors[] = [
                                        'name' => $name,
                                        'checked' => $checked,
                                        'last_visit' => $docLastVisit
                                    ];
                                }
                            }
                            $doctorCount = max(1, min(5, count($konsulDoctors)));

                            // Parse planning items for checkboxes
                            $planningItems = array_filter(array_map('trim', explode("\n", $equipment->planning_pasien ?? '')));
                            $labVal = ''; $labChecked = false;
                            $radVal = ''; $radChecked = false;
                            $konVal = ''; $konChecked = false;
                            $tndVal = ''; $tndChecked = false;
                            $eduVal = ''; $eduChecked = false;
                            $othDetail = '-';
                            foreach($planningItems as $item) {
                                if (stripos($item, 'lab:') !== false || stripos($item, 'lab -') !== false) {
                                    $labChecked = true;
                                    $labVal = trim(preg_replace('/^lab\s*(:|-)\s*/i', '', $item));
                                } elseif (stripos($item, 'radiologi:') !== false || stripos($item, 'radiologi -') !== false) {
                                    $radChecked = true;
                                    $radVal = trim(preg_replace('/^radiologi\s*(:|-)\s*/i', '', $item));
                                } elseif (stripos($item, 'konsul:') !== false || stripos($item, 'konsul -') !== false) {
                                    $konChecked = true;
                                    $konVal = trim(preg_replace('/^konsul\s*(:|-)\s*/i', '', $item));
                                } elseif (stripos($item, 'tindakan:') !== false || stripos($item, 'tindakan -') !== false) {
                                    $tndChecked = true;
                                    $tndVal = trim(preg_replace('/^tindakan\s*(:|-)\s*/i', '', $item));
                                } elseif (stripos($item, 'edukasi:') !== false || stripos($item, 'edukasi -') !== false) {
                                    $eduChecked = true;
                                    $eduVal = trim(preg_replace('/^edukasi\s*(:|-)\s*/i', '', $item));
                                } elseif (stripos($item, 'lain-lain:') !== false || stripos($item, 'lain-lain -') !== false || stripos($item, 'lainnya:') !== false || stripos($item, 'notes:') !== false) {
                                    $othDetail = trim(preg_replace('/^(lain-lain|lainnya|notes)\s*(:|-)\s*/i', '', $item));
                                }
                            }
                            if ($labVal === '-') $labVal = '';
                            if ($radVal === '-') $radVal = '';
                            if ($konVal === '-') $konVal = '';
                            if ($tndVal === '-') $tndVal = '';
                            if ($eduVal === '-') $eduVal = '';

                            // Parse handover shift values
                            $handoverLinesEdit = array_filter(array_map('trim', explode("\n", $equipment->spesifikasi ?? '')));
                            $pagiValue = '';
                            $soreValue = '';
                            $malamValue = '';
                            foreach($handoverLinesEdit as $line) {
                                if (stripos($line, 'pagi:') !== false || stripos($line, 'pagi -') !== false) {
                                    $pagiValue = trim(preg_replace('/^pagi\s*(:|-)\s*/i', '', $line));
                                } elseif (stripos($line, 'sore:') !== false || stripos($line, 'sore -') !== false) {
                                    $soreValue = trim(preg_replace('/^sore\s*(:|-)\s*/i', '', $line));
                                } elseif (stripos($line, 'malam:') !== false || stripos($line, 'malam -') !== false) {
                                    $malamValue = trim(preg_replace('/^malam\s*(:|-)\s*/i', '', $line));
                                }
                            }
                            if ($pagiValue === '-') $pagiValue = '';
                            if ($soreValue === '-') $soreValue = '';
                            if ($malamValue === '-') $malamValue = '';

                            if ($pagiValue === '' && $soreValue === '' && $malamValue === '' && !empty($equipment->spesifikasi)) {
                                $pagiValue = $equipment->spesifikasi;
                            }
                        @endphp
                        <div class="row">
                            <!-- Left Column: INFORMASI MEDIS KLINIS & PEMANTAUAN -->
                            <div class="col-md-6 border-end pe-md-3 mb-4 mb-md-0">
                                <h5 class="fw-bold text-danger mb-3"><i class="mdi mdi-medical-bag text-danger me-1"></i> MEDIS KLINIS & PEMANTAUAN</h5>
                                
                                <div class="row g-2 mb-2">
                                    <div class="col-4">
                                        <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-calendar text-primary me-0.5"></i> Masuk RS (Auto)</label>
                                        <input type="text" class="form-control form-control-sm bg-light text-muted" value="{{ $displayTglMasuk }}" readonly style="cursor: not-allowed; font-size: 0.8rem;">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-clock-outline text-primary me-0.5"></i> LOS (Auto)</label>
                                        <input type="text" class="form-control form-control-sm bg-light text-muted" value="{{ $dynamicLos }}" readonly style="cursor: not-allowed; font-size: 0.8rem;">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-calendar-range text-info me-0.5"></i> Target LOS</label>
                                        <input type="number" name="target_los" class="form-control form-control-sm" value="{{ $equipment->target_los }}" placeholder="Hari" style="font-size: 0.8rem;">
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-doctor text-success me-0.5"></i> DPJP Utama</label>
                                    <div class="input-group input-group-sm shadow-sm">
                                        <input type="text" name="dpjp_utama" class="form-control form-control-sm" value="{{ $equipment->dpjp_utama }}" placeholder="Nama DPJP Utama" list="doctors_list" style="font-size: 0.8rem;">
                                        <div class="input-group-text bg-white py-0">
                                            <div class="form-check mb-0">
                                                <input type="checkbox" name="visit_dpjp_check" id="visit_dpjp_check" class="form-check-input" value="Sudah" {{ !empty($equipment->visit_dpjp) && (stripos($equipment->visit_dpjp, 'tidak') === false) && (stripos($equipment->visit_dpjp, 'belum') === false) ? 'checked' : '' }}>
                                                <label class="form-check-label small fw-bold text-dark mb-0" for="visit_dpjp_check">Visite</label>
                                            </div>
                                        </div>
                                    </div>
                                    @php
                                        $historyLogs = [];
                                        if ($equipment->visit_history) {
                                            $historyLogs = json_decode($equipment->visit_history, true) ?: [];
                                            $historyLogs = array_reverse($historyLogs);
                                        }
                                    @endphp
                                    @if(!empty($historyLogs))
                                        <div class="mt-2 p-2 bg-white rounded border shadow-xs" style="max-height: 120px; overflow-y: auto;">
                                            <span class="text-muted fw-bold d-block mb-1" style="font-size: 0.72rem;"><i class="mdi mdi-history"></i> Riwayat Visite DPJP:</span>
                                            <ul class="list-unstyled mb-0 ps-1" style="font-size: 0.75rem;">
                                                @foreach($historyLogs as $timestamp)
                                                    @php
                                                        $timeParsed = null;
                                                        try {
                                                            $timeParsed = \Carbon\Carbon::parse($timestamp);
                                                        } catch(\Exception $e) {}
                                                    @endphp
                                                    <li class="text-dark py-0.5 border-bottom border-light">
                                                        <i class="mdi mdi-check text-success me-1"></i>
                                                        {{ $timeParsed ? $timeParsed->translatedFormat('d F Y, H:i') . ' WIB' : $timestamp }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>

                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-account-star text-warning me-0.5"></i> NPJA</label>
                                        <input type="text" name="npja" class="form-control form-control-sm" value="{{ $equipment->npja }}" placeholder="Nama NPJA" style="font-size: 0.8rem;">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-heart-pulse text-danger me-0.5"></i> EWS</label>
                                        <select name="ews" class="form-select form-select-sm" style="font-size: 0.8rem;">
                                            <option value="" {{ empty($equipment->ews) ? 'selected' : '' }}>-- Pilih EWS --</option>
                                            @foreach(['Hijau', 'Kuning', 'Oranye', 'Merah', 'DNR'] as $opt)
                                                <option value="{{ $opt }}" {{ $equipment->ews === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-human text-warning me-0.5"></i> Tingkat Ketergantungan</label>
                                        <select name="tingkat_ketergantungan" class="form-select form-select-sm" style="font-size: 0.8rem;">
                                            <option value="" {{ empty($equipment->tingkat_ketergantungan) ? 'selected' : '' }}>-- Pilih Ketergantungan --</option>
                                            @foreach(['Minimal', 'Partial', 'Total'] as $opt)
                                                <option value="{{ $opt }}" {{ $equipment->tingkat_ketergantungan === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-account-network text-warning me-0.5"></i> Ners Bertugas</label>
                                        <input type="text" name="ners_bertugas" class="form-control form-control-sm" value="{{ $equipment->ners_bertugas }}" placeholder="Nama ners" style="font-size: 0.8rem;">
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <div class="p-2 bg-light rounded border shadow-sm">
                                        <label class="fw-bold text-dark mb-2 small" style="font-size: 0.75rem;"><i class="mdi mdi-doctor text-info me-0.5"></i> Dokter Konsul (Maksimal 5)</label>
                                        <div id="doctor_inputs_list">
                                            @for($i = 0; $i < 5; $i++)
                                                @php
                                                    $doc = $konsulDoctors[$i] ?? null;
                                                    $docName = $doc ? $doc['name'] : '';
                                                    $docChecked = $doc ? $doc['checked'] : false;
                                                    $docLastVisit = $doc ? $doc['last_visit'] : '-';
                                                @endphp
                                                <div class="doctor-input-item mb-1.5 align-items-center" id="doc_row_{{ $i }}" style="display: {{ $i < $doctorCount ? 'flex' : 'none' }};">
                                                    <span class="fw-bold me-1.5 small" style="width: 15px; font-size: 0.75rem;">#{{ $i + 1 }}</span>
                                                    <div class="input-group input-group-sm flex-nowrap">
                                                        <input type="text" name="dokter_konsul[]" class="form-control form-control-sm" value="{{ $docName }}" placeholder="Nama Dokter" list="doctors_list" style="font-size: 0.8rem;">
                                                        <div class="input-group-text bg-white py-0">
                                                            <div class="form-check mb-0">
                                                                <input type="checkbox" name="dokter_konsul_check[]" value="{{ $i }}" id="dokter_konsul_check_{{ $i }}" class="form-check-input" {{ $docChecked ? 'checked' : '' }}>
                                                                <label class="form-check-label small fw-bold text-dark mb-0" for="dokter_konsul_check_{{ $i }}">Konsul</label>
                                                            </div>
                                                        </div>
                                                        @if($docLastVisit !== '-')
                                                            <span class="input-group-text bg-light text-muted small" style="font-size: 0.72rem;">{{ $docLastVisit }}</span>
                                                        @endif
                                                    </div>
                                                    @if($i > 0)
                                                        <button type="button" class="btn btn-outline-danger btn-xs ms-1.5 p-1 py-0.5 rounded" onclick="removeDoctorRow({{ $i }})"><i class="mdi mdi-close"></i></button>
                                                    @endif
                                                </div>
                                            @endfor
                                        </div>
                                        <button type="button" id="btn_add_doctor" class="btn btn-outline-primary btn-xs fw-bold mt-1.5" onclick="addDoctorRow()" style="display: {{ $doctorCount < 5 ? 'inline-block' : 'none' }};"><i class="mdi mdi-plus me-1"></i> Tambah</button>

                                        @if(!empty($konsulHistoryMap))
                                            <div class="mt-2 p-2 bg-white rounded border shadow-xs" style="max-height: 120px; overflow-y: auto;">
                                                <span class="text-muted fw-bold d-block mb-1" style="font-size: 0.72rem;"><i class="mdi mdi-history"></i> Riwayat Visite Konsul:</span>
                                                <ul class="list-unstyled mb-0 ps-1" style="font-size: 0.75rem;">
                                                    @foreach($konsulHistoryMap as $docName => $timestamps)
                                                        @if(!empty($timestamps) && !empty($docName))
                                                            <li class="mb-1 text-dark">
                                                                <strong class="text-primary">{{ $docName }}:</strong>
                                                                <div class="ps-2">
                                                                    @foreach(array_reverse($timestamps) as $ts)
                                                                        @php
                                                                            $tsParsed = null;
                                                                            try {
                                                                                $tsParsed = \Carbon\Carbon::parse($ts);
                                                                            } catch(\Exception $e) {}
                                                                        @endphp
                                                                        <div class="text-muted border-bottom border-light py-0.5">
                                                                            <i class="mdi mdi-check text-success me-1"></i>
                                                                            {{ $tsParsed ? $tsParsed->translatedFormat('d F Y, H:i') . ' WIB' : $ts }}
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            </li>
                                                        @endif
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-clipboard-text text-dark me-0.5"></i> Diagnosis Medis</label>
                                    <input type="text" name="type" class="form-control form-control-sm" value="{{ $equipment->type }}" placeholder="Diagnosis medis" style="font-size: 0.8rem;">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label text-danger fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-clipboard-text text-danger me-0.5"></i> Diagnosis Lokal</label>
                                    <textarea name="diagnosis_lokal" class="form-control form-control-sm fw-bold text-dark" rows="2" placeholder="Tulis diagnosis lokal di sini..." style="font-size: 0.8rem;">{{ $equipment->diagnosis_lokal }}</textarea>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-lightbulb-on text-primary me-0.5"></i> Planning Pasien</label>
                                    <div class="p-2 bg-light rounded border">
                                        <!-- LAB -->
                                        <div class="mb-1.5">
                                            <div class="form-check mb-0.5">
                                                <input type="checkbox" name="planning_lab_check" id="planning_lab_check" class="form-check-input" value="1" {{ $labChecked ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold text-dark small" for="planning_lab_check">Laboratorium (Lab)</label>
                                            </div>
                                            <div id="planning_lab_container" style="display: {{ $labChecked ? 'block' : 'none' }}; margin-left: 20px;">
                                                <input type="text" name="planning_lab" class="form-control form-control-sm" value="{{ $labVal }}" placeholder="Planning Lab" style="font-size: 0.8rem;">
                                            </div>
                                        </div>
                                        
                                        <!-- RADIOLOGI -->
                                        <div class="mb-1.5">
                                            <div class="form-check mb-0.5">
                                                <input type="checkbox" name="planning_radiologi_check" id="planning_radiologi_check" class="form-check-input" value="1" {{ $radChecked ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold text-dark small" for="planning_radiologi_check">Radiologi</label>
                                            </div>
                                            <div id="planning_radiologi_container" style="display: {{ $radChecked ? 'block' : 'none' }}; margin-left: 20px;">
                                                <input type="text" name="planning_radiologi" class="form-control form-control-sm" value="{{ $radVal }}" placeholder="Planning Radiologi" style="font-size: 0.8rem;">
                                            </div>
                                        </div>
                                        
                                        <!-- KONSUL -->
                                        <div class="mb-1.5">
                                            <div class="form-check mb-0.5">
                                                <input type="checkbox" name="planning_konsul_check" id="planning_konsul_check" class="form-check-input" value="1" {{ $konChecked ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold text-dark small" for="planning_konsul_check">Konsul</label>
                                            </div>
                                            <div id="planning_konsul_container" style="display: {{ $konChecked ? 'block' : 'none' }}; margin-left: 20px;">
                                                <input type="text" name="planning_konsul" class="form-control form-control-sm" value="{{ $konVal }}" placeholder="Planning Konsul" style="font-size: 0.8rem;">
                                            </div>
                                        </div>
                                        
                                        <!-- TINDAKAN -->
                                        <div class="mb-1.5">
                                            <div class="form-check mb-0.5">
                                                <input type="checkbox" name="planning_tindakan_check" id="planning_tindakan_check" class="form-check-input" value="1" {{ $tndChecked ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold text-dark small" for="planning_tindakan_check">Tindakan</label>
                                            </div>
                                            <div id="planning_tindakan_container" style="display: {{ $tndChecked ? 'block' : 'none' }}; margin-left: 20px;">
                                                <input type="text" name="planning_tindakan" class="form-control form-control-sm" value="{{ $tndVal }}" placeholder="Planning Tindakan" style="font-size: 0.8rem;">
                                            </div>
                                        </div>
                                        
                                        <!-- EDUKASI -->
                                        <div>
                                            <div class="form-check mb-0.5">
                                                <input type="checkbox" name="planning_edukasi_check" id="planning_edukasi_check" class="form-check-input" value="1" {{ $eduChecked ? 'checked' : '' }}>
                                                <label class="form-check-label fw-bold text-dark small" for="planning_edukasi_check">Edukasi / Dll</label>
                                            </div>
                                            <div id="planning_edukasi_container" style="display: {{ $eduChecked ? 'block' : 'none' }}; margin-left: 20px;">
                                                <input type="text" name="planning_edukasi" class="form-control form-control-sm" value="{{ $eduVal }}" placeholder="Planning Edukasi" style="font-size: 0.8rem;">
                                            </div>
                                        </div>

                                        <!-- NOTES TAMBAHAN -->
                                        <div class="mt-2.5 pt-2 border-top">
                                            <label class="fw-bold text-dark small mb-1" for="planning_lain_lain"><i class="mdi mdi-note-text-outline text-muted me-0.5"></i> Notes Tambahan (Freetext)</label>
                                            <textarea name="planning_lain_lain" id="planning_lain_lain" class="form-control form-control-sm text-dark fw-bold" rows="2" placeholder="Tulis catatan tambahan lainnya..." style="font-size: 0.8rem;">{{ $othDetail !== '-' ? $othDetail : '' }}</textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-logout text-success me-0.5"></i> Rencana Pulang</label>
                                    @php
                                        $rpValue = trim($equipment->rencana_pulang ?? '');
                                    @endphp
                                    <select name="rencana_pulang" class="form-select form-select-sm fw-bold" style="font-size: 0.8rem;">
                                        <option value="" {{ $rpValue === '' ? 'selected' : '' }}>-</option>
                                        <option value="Hari Ini" {{ (stripos($rpValue, 'hari ini') !== false) ? 'selected' : '' }} style="color: #198754; font-weight: bold;">Hari Ini</option>
                                        <option value="Besok" {{ (stripos($rpValue, 'besok') !== false) ? 'selected' : '' }} style="color: #0d6efd; font-weight: bold;">Besok</option>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-needle text-danger me-0.5"></i> Penggunaan Alkes & Alat Invasif (Barrier)</label>
                                    <div class="dropdown" id="alkes_dropdown_container">
                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center" type="button" id="alkesDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.8rem; background-color: #fff;">
                                            <span id="alkesDropdownLabel" class="text-truncate">Pilih Alkes / Alat Invasif</span>
                                        </button>
                                        <ul class="dropdown-menu w-100 p-2" aria-labelledby="alkesDropdownBtn" style="max-height: 250px; overflow-y: auto; font-size: 0.8rem;">
                                            @php
                                                $alkesOptions = [
                                                    'Syringe pump', 'Infusion Pump', 'Monitor', 'Oksigen', 'Kasur Dekubitus', 
                                                    'Nebulizer', 'Suction', 'Blower', 'NGT/OGT', 'IV Perifer', 'CVC', 
                                                    'Kateter urin', 'Drain', 'NJFT', 'HFNC', 'PEG', 'Cimino', 'Stoma', 
                                                    'CDL', 'Chemoport', 'Trakeostomi'
                                                ];
                                                $currentAlkes = array_map('trim', explode(',', $equipment->alkes_invasif ?? ''));
                                            @endphp
                                            @foreach($alkesOptions as $opt)
                                                <li>
                                                    <div class="form-check dropdown-item py-1">
                                                        <input class="form-check-input alkes-checkbox" type="checkbox" value="{{ $opt }}" id="alkes_chk_{{ $loop->index }}" {{ in_array($opt, $currentAlkes) ? 'checked' : '' }}>
                                                        <label class="form-check-label w-100 cursor-pointer mb-0" for="alkes_chk_{{ $loop->index }}">
                                                            {{ $opt }}
                                                        </label>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <input type="hidden" name="alkes_invasif" id="alkes_invasif_hidden" value="{{ $equipment->alkes_invasif }}">
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-medical-bag text-primary me-0.5"></i> Tindakan / Terapi Medis</label>
                                    <div class="dropdown" id="tindakan_dropdown_container">
                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle w-100 text-start d-flex justify-content-between align-items-center" type="button" id="tindakanDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.8rem; background-color: #fff;">
                                            <span id="tindakanDropdownLabel" class="text-truncate">Pilih Tindakan / Terapi Medis</span>
                                        </button>
                                        <ul class="dropdown-menu w-100 p-2" aria-labelledby="tindakanDropdownBtn" style="max-height: 200px; overflow-y: auto; font-size: 0.8rem;">
                                            @php
                                                $tindakanOptions = [
                                                    'Kemoterapi', 'EKG', 'GV', 'Konseling Laktasi', 'Pasang Infus', 'CTG', 'USG/Echo'
                                                ];
                                                $currentTindakan = array_map('trim', explode(',', $equipment->tindakan_detail ?? ''));
                                            @endphp
                                            @foreach($tindakanOptions as $opt)
                                                <li>
                                                    <div class="form-check dropdown-item py-1">
                                                        <input class="form-check-input tindakan-checkbox" type="checkbox" value="{{ $opt }}" id="tindakan_chk_{{ $loop->index }}" {{ in_array($opt, $currentTindakan) ? 'checked' : '' }}>
                                                        <label class="form-check-label w-100 cursor-pointer mb-0" for="tindakan_chk_{{ $loop->index }}">
                                                            {{ $opt }}
                                                        </label>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <input type="hidden" name="tindakan_detail" id="tindakan_detail_hidden" value="{{ $equipment->tindakan_detail }}">
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-repeat text-warning me-0.5"></i> Catatan Handover Per Shift</label>
                                    <div class="p-2 bg-light rounded border">
                                        <div class="row g-2 mb-1.5">
                                            <div class="col-8">
                                                <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.72rem;"><i class="mdi mdi-white-balance-sunny text-warning me-0.5"></i> Pagi</label>
                                                <textarea name="handover_pagi" class="form-control form-control-sm" rows="1" placeholder="Pagi..." style="font-size: 0.8rem;">{{ $pagiValue }}</textarea>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.72rem;">Ners Pagi</label>
                                                <input type="text" name="ners_pagi" class="form-control form-control-sm" value="{{ $equipment->ners_pagi }}" placeholder="Ners Pagi" style="font-size: 0.8rem;">
                                            </div>
                                        </div>
                                        <div class="row g-2 mb-1.5">
                                            <div class="col-8">
                                                <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.72rem;"><i class="mdi mdi-weather-sunset text-primary me-0.5"></i> Sore</label>
                                                <textarea name="handover_sore" class="form-control form-control-sm" rows="1" placeholder="Sore..." style="font-size: 0.8rem;">{{ $soreValue }}</textarea>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.72rem;">Ners Siang</label>
                                                <input type="text" name="ners_siang" class="form-control form-control-sm" value="{{ $equipment->ners_siang }}" placeholder="Ners Siang" style="font-size: 0.8rem;">
                                            </div>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-8">
                                                <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.72rem;"><i class="mdi mdi-weather-night text-info me-0.5"></i> Malam</label>
                                                <textarea name="handover_malam" class="form-control form-control-sm" rows="1" placeholder="Malam..." style="font-size: 0.8rem;">{{ $malamValue }}</textarea>
                                            </div>
                                            <div class="col-4">
                                                <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.72rem;">Ners Malam</label>
                                                <input type="text" name="ners_malam" class="form-control form-control-sm" value="{{ $equipment->ners_malam }}" placeholder="Ners Malam" style="font-size: 0.8rem;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: KEBUTUHAN CASE MANAGER -->
                            <div class="col-md-6 ps-md-3">
                                <h5 class="fw-bold text-primary mb-3"><i class="mdi mdi-clipboard-text-play text-primary me-1"></i> KEBUTUHAN CASE MANAGER</h5>
                                
                                <div class="row g-2 mb-2">
                                    <div class="col-4">
                                        <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-cash-multiple text-success me-0.5"></i> Billing</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white fw-bold py-0">Rp</span>
                                            <input type="text" name="billing_aktual" id="billing_aktual" class="form-control form-control-sm" value="{{ $equipment->billing_aktual ? number_format($equipment->billing_aktual, 0, ',', '.') : '' }}" placeholder="Rp" style="font-size: 0.8rem;">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-cash text-danger me-0.5"></i> PAGU</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white fw-bold py-0">Rp</span>
                                            <input type="text" name="pagu_budget" id="pagu_budget" class="form-control form-control-sm" value="{{ $equipment->pagu_budget ? number_format($equipment->pagu_budget, 0, ',', '.') : '' }}" placeholder="Rp" style="font-size: 0.8rem;">
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-chart-percent text-info me-0.5"></i> % vs Pagu</label>
                                        <input type="text" id="persentase_pagu_display" class="form-control form-control-sm bg-light text-muted" value="{{ $equipment->persentase_pagu }}" readonly placeholder="Otomatis" style="cursor: not-allowed; font-size: 0.8rem;">
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-tag-text-outline text-warning me-0.5"></i> Kategori Pasien</label>
                                    <select name="kategori_pasien" class="form-select form-select-sm" style="font-size: 0.8rem;">
                                        <option value="" {{ empty($equipment->kategori_pasien) ? 'selected' : '' }}>-- Pilih Kategori Pasien --</option>
                                        @foreach(['IGD Medis', 'IGD Surgikal', 'Elektif Medis', 'Elektif Surgikal', 'Elektif Kemoterapi'] as $cat)
                                            <option value="{{ $cat }}" {{ $equipment->kategori_pasien === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-note-text text-secondary me-0.5"></i> Notes NUM</label>
                                    <textarea name="notes_num" class="form-control form-control-sm" rows="2" placeholder="Catatan NUM..." style="font-size: 0.8rem;">{{ $equipment->notes_num }}</textarea>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-note-outline text-secondary me-0.5"></i> Notes Case Manager</label>
                                    <textarea name="notes_case_manager" class="form-control form-control-sm" rows="2" placeholder="Catatan Case Manager..." style="font-size: 0.8rem;">{{ $equipment->notes_case_manager }}</textarea>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-flask-outline text-primary me-0.5"></i> Riw Pemeriksaan Lab (singkat)</label>
                                    <textarea name="riw_lab" class="form-control form-control-sm" rows="2" placeholder="Contoh: GDS 1/6, 2/6 ; Elektrolit 1/6 4/6" style="font-size: 0.8rem;">{{ $equipment->riw_lab }}</textarea>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-video-outline text-success me-0.5"></i> Riw Pemeriksaan Rad (singkat)</label>
                                    <textarea name="riw_rad" class="form-control form-control-sm" rows="2" placeholder="Contoh: Rad toraks 1/6. 4/6 ; CT Brain NK 1/6" style="font-size: 0.8rem;">{{ $equipment->riw_rad }}</textarea>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-pill text-danger me-0.5"></i> Riw Obat Mahal / Antibiotik (singkat)</label>
                                    <textarea name="riw_obat" class="form-control form-control-sm" rows="2" placeholder="Tuliskan riwayat obat..." style="font-size: 0.8rem;">{{ $equipment->riw_obat }}</textarea>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-file-document-box-outline text-dark me-0.5"></i> Rencana Prosedur</label>
                                    <textarea name="rencana_prosedur" class="form-control form-control-sm" rows="2" placeholder="Rencana prosedur..." style="font-size: 0.8rem;">{{ $equipment->rencana_prosedur }}</textarea>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-file-find text-dark me-0.5"></i> Rencana Diagnostik</label>
                                    <textarea name="rencana_diagnostik" class="form-control form-control-sm" rows="2" placeholder="Rencana diagnostik..." style="font-size: 0.8rem;">{{ $equipment->rencana_diagnostik }}</textarea>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label text-dark fw-bold small mb-0.5" style="font-size: 0.75rem;"><i class="mdi mdi-forum-outline text-dark me-0.5"></i> Rencana Konsul</label>
                                    <textarea name="rencana_konsul" class="form-control form-control-sm" rows="2" placeholder="Rencana konsul..." style="font-size: 0.8rem;">{{ $equipment->rencana_konsul }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 pt-3 border-top d-flex justify-content-between">
                            <a href="{{ route('maintenances.index') }}" class="btn btn-light fw-bold px-4 btn-sm">Batal</a>
                            <button type="submit" class="btn btn-success text-white fw-bold px-4 btn-sm">
                                <i class="mdi mdi-content-save me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        // --- Dokter Konsul Row Management ---
        let activeDoctorsCount = {{ $doctorCount }};
        
        window.addDoctorRow = function() {
            if (activeDoctorsCount < 5) {
                document.getElementById('doc_row_' + activeDoctorsCount).style.display = 'flex';
                activeDoctorsCount++;
            }
            if (activeDoctorsCount === 5) {
                document.getElementById('btn_add_doctor').style.display = 'none';
            }
        };

        window.removeDoctorRow = function(index) {
            const row = document.getElementById('doc_row_' + index);
            const input = row.querySelector('input[name="dokter_konsul[]"]');
            const checkbox = row.querySelector('input[type="checkbox"]');
            input.value = '';
            checkbox.checked = false;
            row.style.display = 'none';
            
            // Shift subsequent inputs and checkbox states up
            for (let i = index; i < 4; i++) {
                const nextRow = document.getElementById('doc_row_' + (i + 1));
                const currentInput = document.getElementById('doc_row_' + i).querySelector('input[name="dokter_konsul[]"]');
                const currentCheckbox = document.getElementById('doc_row_' + i).querySelector('input[type="checkbox"]');
                
                const nextInput = nextRow.querySelector('input[name="dokter_konsul[]"]');
                const nextCheckbox = nextRow.querySelector('input[type="checkbox"]');
                
                currentInput.value = nextInput.value;
                currentCheckbox.checked = nextCheckbox.checked;
                
                document.getElementById('doc_row_' + i).style.display = nextRow.style.display;
            }
            
            const lastRow = document.getElementById('doc_row_4');
            lastRow.querySelector('input[name="dokter_konsul[]"]').value = '';
            lastRow.querySelector('input[type="checkbox"]').checked = false;
            lastRow.style.display = 'none';
            
            if (activeDoctorsCount > 1) {
                activeDoctorsCount--;
            }
            document.getElementById('btn_add_doctor').style.display = 'inline-block';
        };

        // --- Planning Checkbox Toggles ---
        ['lab', 'radiologi', 'konsul', 'tindakan', 'edukasi'].forEach(function(item) {
            const checkbox = document.getElementById('planning_' + item + '_check');
            if (checkbox) {
                checkbox.addEventListener('change', function() {
                    const container = document.getElementById('planning_' + item + '_container');
                    if (container) {
                        if (this.checked) {
                            container.style.display = 'block';
                        } else {
                            container.style.display = 'none';
                            const inputField = container.querySelector('input');
                            if (inputField) inputField.value = '';
                        }
                    }
                });
            }
        });
        // --- Billing and PAGU currency formatting & percentage calculation ---
        const billingInput = document.getElementById('billing_aktual');
        const paguInput = document.getElementById('pagu_budget');
        const persentaseDisplay = document.getElementById('persentase_pagu_display');

        function cleanNumber(val) {
            return parseInt(val.replace(/[^0-9]/g, '')) || 0;
        }

        function formatRupiah(val) {
            let num = val.replace(/[^0-9]/g, '');
            if (!num) return '';
            return parseInt(num).toLocaleString('id-ID');
        }

        function updatePercentage() {
            let billing = cleanNumber(billingInput.value);
            let pagu = cleanNumber(paguInput.value);
            if (pagu > 0) {
                let pct = Math.round((billing / pagu) * 100);
                persentaseDisplay.value = pct + '%';
            } else {
                persentaseDisplay.value = '';
            }
        }
        if (billingInput && paguInput) {
            [billingInput, paguInput].forEach(input => {
                input.addEventListener('input', function(e) {
                    let cursorPosition = this.selectionStart;
                    let originalLength = this.value.length;
                    
                    let formatted = formatRupiah(this.value);
                    this.value = formatted;
                    
                    let newLength = formatted.length;
                    cursorPosition = cursorPosition + (newLength - originalLength);
                    this.setSelectionRange(cursorPosition, cursorPosition);
                    
                    updatePercentage();
                });
            });
        }

        // --- Multi-select checkboxes for Alkes Invasif & Tindakan ---
        const alkesCheckboxes = document.querySelectorAll('.alkes-checkbox');
        const alkesHiddenInput = document.getElementById('alkes_invasif_hidden');
        const alkesDropdownLabel = document.getElementById('alkesDropdownLabel');

        function updateAlkesSelection() {
            const selected = [];
            alkesCheckboxes.forEach(cb => {
                if (cb.checked) {
                    selected.push(cb.value);
                }
            });
            const selectedStr = selected.join(', ');
            if (alkesHiddenInput) {
                alkesHiddenInput.value = selectedStr;
            }
            if (alkesDropdownLabel) {
                alkesDropdownLabel.innerText = selected.length > 0 ? selectedStr : 'Pilih Alkes / Alat Invasif';
            }
        }

        if (alkesCheckboxes.length > 0) {
            alkesCheckboxes.forEach(cb => {
                cb.addEventListener('change', updateAlkesSelection);
            });
            // Stop click propagation to prevent dropdown close on selection
            const alkesMenu = document.querySelector('#alkes_dropdown_container .dropdown-menu');
            if (alkesMenu) {
                alkesMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            updateAlkesSelection();
        }

        const tindakanCheckboxes = document.querySelectorAll('.tindakan-checkbox');
        const tindakanHiddenInput = document.getElementById('tindakan_detail_hidden');
        const tindakanDropdownLabel = document.getElementById('tindakanDropdownLabel');

        function updateTindakanSelection() {
            const selected = [];
            tindakanCheckboxes.forEach(cb => {
                if (cb.checked) {
                    selected.push(cb.value);
                }
            });
            const selectedStr = selected.join(', ');
            if (tindakanHiddenInput) {
                tindakanHiddenInput.value = selectedStr;
            }
            if (tindakanDropdownLabel) {
                tindakanDropdownLabel.innerText = selected.length > 0 ? selectedStr : 'Pilih Tindakan / Terapi Medis';
            }
        }

        if (tindakanCheckboxes.length > 0) {
            tindakanCheckboxes.forEach(cb => {
                cb.addEventListener('change', updateTindakanSelection);
            });
            // Stop click propagation to prevent dropdown close on selection
            const tindakanMenu = document.querySelector('#tindakan_dropdown_container .dropdown-menu');
            if (tindakanMenu) {
                tindakanMenu.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            updateTindakanSelection();
        }
    });
</script>
@php
    $doctorsList = \App\Models\Doctor::orderBy('name')->get();
@endphp
<datalist id="doctors_list">
    @foreach($doctorsList as $doc)
        <option value="{{ $doc->name }}">{{ $doc->ksm }}</option>
    @endforeach
</datalist>
@stop
