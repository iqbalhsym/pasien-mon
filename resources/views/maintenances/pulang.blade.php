@extends('layouts.staradmin')

@section('title', 'Daftar Pasien Sudah Pulang')

@section('content_header')
<div class="d-sm-flex align-items-center justify-content-between mb-2">
    <div class="d-flex align-items-center">
        <div class="bg-secondary text-white rounded p-2.5 me-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
            <i class="mdi mdi-account-off fs-3"></i>
        </div>
        <div>
            <h2 class="h3 font-weight-bold mb-0 text-secondary d-flex align-items-center" style="letter-spacing: -0.5px;">
                DAFTAR PASIEN SUDAH PULANG (DISCHARGED)
                <span class="badge bg-secondary ms-2 fw-bold text-white" style="font-size: 1.1rem; border-radius: 8px; padding: 4px 10px;">{{ $equipmentsPaginator->total() }}</span>
            </h2>
        </div>
    </div>
    <div class="d-flex gap-2 mt-3 mt-sm-0 align-items-center flex-wrap">
        <!-- Search + Sort Form -->
        <form action="{{ route('maintenances.pulang') }}" method="GET" class="d-flex gap-2 flex-wrap align-items-center" id="pulangFilterForm">
            <!-- Search -->
            <div class="input-group shadow-sm" style="min-width: 260px;">
                <span class="input-group-text bg-white border-end-0"><i class="mdi mdi-magnify text-muted fs-5"></i></span>
                <input type="text" name="search" class="form-control border-start-0 ps-0 bg-white fw-bold text-dark" placeholder="Cari nama / No. RM / DPJP" value="{{ $search }}" style="font-size: 0.92rem;">
            </div>

            <!-- Sort Buttons -->
            <div class="btn-group shadow-sm" role="group">
                <a href="{{ route('maintenances.pulang', array_merge(request()->except(['sort', 'page']), ['sort' => 'terbaru'])) }}"
                   class="btn btn-sm fw-bold {{ $sort === 'terbaru' ? 'btn-secondary text-white' : 'btn-light border text-dark bg-white' }}" style="font-size: 0.82rem;" title="Urut: Terbaru">
                    <i class="mdi mdi-sort-clock-descending-outline me-1"></i>Terbaru
                </a>
                <a href="{{ route('maintenances.pulang', array_merge(request()->except(['sort', 'page']), ['sort' => 'alphabetical'])) }}"
                   class="btn btn-sm fw-bold {{ $sort === 'alphabetical' ? 'btn-primary text-white' : 'btn-light border text-dark bg-white' }}" style="font-size: 0.82rem;" title="Urut: A-Z">
                    <i class="mdi mdi-sort-alphabetical-ascending me-1"></i>Nama A-Z
                </a>
                <a href="{{ route('maintenances.pulang', array_merge(request()->except(['sort', 'page']), ['sort' => 'alphabetical_desc'])) }}"
                   class="btn btn-sm fw-bold {{ $sort === 'alphabetical_desc' ? 'btn-primary text-white' : 'btn-light border text-dark bg-white' }}" style="font-size: 0.82rem;" title="Urut: Z-A">
                    <i class="mdi mdi-sort-alphabetical-descending me-1"></i>Nama Z-A
                </a>
            </div>

            <!-- Per Page Dropdown -->
            <div class="input-group shadow-sm" style="width: auto;">
                <span class="input-group-text bg-white border-end-0 py-0" style="font-size: 0.85rem;"><i class="mdi mdi-format-list-numbered text-muted"></i></span>
                <select name="per_page" class="form-select border-start-0 ps-0 bg-white fw-bold text-dark" style="font-size: 0.88rem;" onchange="document.getElementById('pulangFilterForm').submit();">
                    <option value="10" {{ request('per_page') == '10' ? 'selected' : '' }}>10 baris</option>
                    <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25 baris</option>
                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50 baris</option>
                    <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100 baris</option>
                </select>
            </div>

            <!-- Submit & Reset -->
            <button type="submit" class="btn btn-outline-secondary btn-sm shadow-sm" style="height: 38px; width: 38px;" title="Cari">
                <i class="mdi mdi-magnify fs-5"></i>
            </button>
            <a href="{{ route('maintenances.pulang') }}" class="btn btn-light border bg-white shadow-sm d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="Reset / Refresh">
                <i class="mdi mdi-refresh text-dark fs-4"></i>
            </a>
        </form>
    </div>
</div>
@stop

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive bg-white">
                    <table class="table table-hover table-striped align-middle mb-0" style="min-width: 1300px;">
                        <thead class="bg-light border-bottom text-dark">
                            <tr>
                                <th class="text-center py-3 fw-bold" style="width: 120px; font-size: 0.88rem; color: #4B5563;">Status Bed</th>
                                <th class="py-3 fw-bold" style="width: 250px; font-size: 0.88rem; color: #4B5563;">Nama Pasien<br><span class="text-muted fw-normal" style="font-size: 0.75rem;">No. RM | Jenis Kelamin | Umur<br>Diagnosa Medis</span></th>
                                <th class="py-3 fw-bold" style="width: 140px; font-size: 0.88rem; color: #4B5563;">Tgl Masuk</th>
                                <th class="py-3 fw-bold" style="width: 220px; font-size: 0.88rem; color: #4B5563;">DPJP Utama & Konsul</th>
                                <th class="py-3 fw-bold" style="width: 220px; font-size: 0.88rem; color: #4B5563;">Handover<br><span class="text-muted fw-normal" style="font-size: 0.75rem;">Pagi | Sore | Malam</span></th>
                                <th class="py-3 fw-bold" style="width: 240px; font-size: 0.88rem; color: #4B5563;">Planning Selama Perawatan<br><span class="text-muted fw-normal" style="font-size: 0.75rem;">Lab | Radiologi | Konsul | Tindakan | Dll</span></th>
                                <th class="py-3 fw-bold" style="width: 180px; font-size: 0.88rem; color: #4B5563;">Barrier</th>
                                <th class="py-3 fw-bold" style="width: 140px; font-size: 0.88rem; color: #4B5563;">Rencana Pulang</th>
                                <th class="py-3 fw-bold text-center" style="width: 120px; font-size: 0.88rem; color: #4B5563;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($equipmentsPaginator as $key => $eq)
                            @php
                                $tglMasukRaw = $eq->registered_date ?: $eq->tanggal_pengadaan;
                                $tglMasukParsed = null;
                                try {
                                    $tglMasukParsed = \Carbon\Carbon::parse($tglMasukRaw);
                                } catch (\Exception $e) {}
                                
                                $dayNamesIndonesian = [
                                    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 
                                    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
                                ];
                                $displayHariMasuk = $tglMasukParsed ? $dayNamesIndonesian[$tglMasukParsed->format('l')] ?? $tglMasukParsed->format('l') : '';
                                $displayTglMasuk = $tglMasukParsed ? $tglMasukParsed->format('d/m/Y') : ($tglMasukRaw ?: '-');

                                // Handover Parsing
                                $handoverLines = array_filter(array_map('trim', explode("\n", $eq->spesifikasi ?? '')));
                                $pagiNote = '-'; $soreNote = '-'; $malamNote = '-';
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
                                $othDetail = '-';
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
                                    } elseif (stripos($item, 'lain-lain:') !== false || stripos($item, 'lain-lain -') !== false || stripos($item, 'lainnya:') !== false || stripos($item, 'notes:') !== false) {
                                         $othDetail = trim(preg_replace('/^(lain-lain|lainnya|notes)\s*(:|-)\s*/i', '', $item));
                                     }
                                }
                                if (!$labCheck && !$radCheck && !$konCheck && !$tndCheck && !$eduCheck && !empty($planningText)) {
                                    $tndCheck = true;
                                    $tndDetail = $planningText;
                                }

                                // Dokter Konsul Parsing
                                $rawDokterKonsul = $eq->dokter_konsul ?? '';
                                $cleanDokterKonsul = '';
                                if (!empty($rawDokterKonsul)) {
                                    $parts = explode(',', $rawDokterKonsul);
                                    $names = [];
                                    foreach($parts as $part) {
                                        $part = trim($part);
                                        if ($part === '') continue;
                                        $name = $part;
                                        if (strpos($part, '[v] ') === 0 || strpos($part, '[ ] ') === 0) {
                                            $name = substr($part, 4);
                                        }
                                        $names[] = $name;
                                    }
                                    $cleanDokterKonsul = implode(', ', $names);
                                }
                            @endphp
                            <tr class="border-bottom">
                                <!-- Status Bed -->
                                <td class="text-center fw-bold text-muted bg-light-subtle">
                                    <span class="badge bg-secondary px-3 py-1.5 fw-bold" style="font-size: 0.82rem; border-radius: 6px;">Sudah Pulang</span>
                                </td>
                                
                                <!-- Nama Pasien -->
                                <td>
                                    <div class="fw-bold text-uppercase mb-1" style="font-size: 0.95rem; color: #6c757d; line-height: 1.2;">
                                        {{ $eq->merk }}
                                    </div>
                                    <div class="text-muted mb-1" style="font-size: 0.85rem; font-weight: 500;">
                                        RM. {{ $eq->serial_number }}
                                    </div>
                                    <div class="mb-1" style="font-size: 0.85rem; font-weight: 600; color: #6c757d;">
                                        <i class="mdi {{ in_array(strtolower($eq->gender), ['laki-laki', 'male', 'l']) ? 'mdi-gender-male' : 'mdi-gender-female' }} me-1"></i>
                                        {{ in_array(strtolower($eq->gender), ['laki-laki', 'male', 'l']) ? 'Laki-laki' : 'Perempuan' }} | {{ $eq->tanggal_lahir ? \Carbon\Carbon::parse($eq->tanggal_lahir)->age : '-' }} Th
                                    </div>
                                    <div class="text-muted small mb-1" style="font-size: 0.82rem; font-weight: 500;">
                                        {{ $eq->type }}
                                    </div>
                                    @if($eq->diagnosis_lokal)
                                        <div class="text-muted fw-bold mb-2" style="font-size: 0.82rem; font-style: italic;">
                                            Diagnosis Lokal: {{ $eq->diagnosis_lokal }}
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

                                <!-- DPJP Utama & Konsul -->
                                <td>
                                    <div style="font-size: 0.88rem; line-height: 1.4;">
                                        <div class="fw-bold text-dark mb-1">DPJP: <span class="text-muted">{{ $eq->dpjp_utama ?: '-' }}</span></div>
                                        @if(!empty($cleanDokterKonsul))
                                            <div class="text-muted small mt-1">
                                                <strong>Konsul:</strong> {{ $cleanDokterKonsul }}
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Handover -->
                                <td>
                                    <div style="font-size: 0.85rem; line-height: 1.45;">
                                        <div class="mb-1">
                                            <span class="text-muted fw-bold">Pagi:</span> <span class="text-dark">{{ $pagiNote }}</span>
                                            @if($eq->ners_pagi)
                                                <div class="text-muted small ps-2" style="font-size: 0.72rem;">Ners: {{ $eq->ners_pagi }}</div>
                                            @endif
                                        </div>
                                        <div class="mb-1">
                                            <span class="text-muted fw-bold">Sore:</span> <span class="text-dark">{{ $soreNote }}</span>
                                            @if($eq->ners_siang)
                                                <div class="text-muted small ps-2" style="font-size: 0.72rem;">Ners: {{ $eq->ners_siang }}</div>
                                            @endif
                                        </div>
                                        <div>
                                            <span class="text-muted fw-bold">Malam:</span> <span class="text-dark">{{ $malamNote }}</span>
                                            @if($eq->ners_malam)
                                                <div class="text-muted small ps-2" style="font-size: 0.72rem;">Ners: {{ $eq->ners_malam }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Planning Selama Perawatan -->
                                <td>
                                    <div style="font-size: 0.82rem; line-height: 1.4;">
                                        <div class="d-flex align-items-center mb-1 text-muted">
                                            <i class="mdi {{ $labCheck ? 'mdi-checkbox-marked text-success' : 'mdi-checkbox-blank-outline' }} me-1.5" style="font-size: 1.1rem;"></i>
                                            <span>Lab: <span class="fw-bold">{{ $labDetail }}</span></span>
                                        </div>
                                        <div class="d-flex align-items-center mb-1 text-muted">
                                            <i class="mdi {{ $radCheck ? 'mdi-checkbox-marked text-success' : 'mdi-checkbox-blank-outline' }} me-1.5" style="font-size: 1.1rem;"></i>
                                            <span>Radiologi: <span class="fw-bold">{{ $radDetail }}</span></span>
                                        </div>
                                        <div class="d-flex align-items-center mb-1 text-muted">
                                            <i class="mdi {{ $konCheck ? 'mdi-checkbox-marked text-success' : 'mdi-checkbox-blank-outline' }} me-1.5" style="font-size: 1.1rem;"></i>
                                            <span>Konsul: <span class="fw-bold">{{ $konDetail }}</span></span>
                                        </div>
                                        <div class="d-flex align-items-center mb-1 text-muted">
                                            <i class="mdi {{ $tndCheck ? 'mdi-checkbox-marked text-success' : 'mdi-checkbox-blank-outline' }} me-1.5" style="font-size: 1.1rem;"></i>
                                            <span>Tindakan: <span class="fw-bold">{{ $tndDetail }}</span></span>
                                        </div>
                                        <div class="d-flex align-items-center mb-1 text-muted">
                                            <i class="mdi {{ $eduCheck ? 'mdi-checkbox-marked text-success' : 'mdi-checkbox-blank-outline' }} me-1.5" style="font-size: 1.1rem;"></i>
                                            <span>Edukasi/Dll: <span class="fw-bold">{{ $eduDetail }}</span></span>
                                        </div>
                                        @if(!empty($othDetail) && $othDetail !== '-')
                                            <div class="d-flex align-items-start mt-1 text-muted" style="font-size: 0.78rem;">
                                                <i class="mdi mdi-note-text-outline me-1.5 mt-0.5" style="font-size: 0.95rem;"></i>
                                                <span>Notes: <span class="fw-bold text-dark">{{ $othDetail }}</span></span>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <!-- Barrier -->
                                <td>
                                    <div class="text-muted" style="font-size: 0.88rem; font-weight: 500; line-height: 1.4;">
                                        {{ $eq->alkes_invasif ?: '-' }}
                                    </div>
                                </td>

                                <!-- Rencana Pulang -->
                                <td>
                                    <span class="badge bg-light text-dark border px-2.5 py-1.5 fw-bold" style="font-size: 0.85rem;">
                                        {{ $eq->rencana_pulang ?: '-' }}
                                    </span>
                                </td>

                                <!-- Aksi -->
                                <td class="text-center">
                                    <div class="dropdown w-100">
                                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle fw-bold w-100 py-1 shadow-sm text-dark bg-white" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 0.8rem; border-radius: 8px;">
                                            Menu
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius: 12px; min-width: 160px;">
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
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="mdi mdi-text-box-search-outline text-muted" style="font-size: 4rem;"></i>
                                    <h4 class="mt-3 text-dark fw-bold">Tidak Ada Data Pasien Pulang</h4>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top bg-light flex-wrap gap-2">
                    <div class="text-muted small fw-bold">
                        Menampilkan {{ $equipmentsPaginator->firstItem() ?: 0 }} - {{ $equipmentsPaginator->lastItem() ?: 0 }} dari {{ $equipmentsPaginator->total() }} pasien
                    </div>
                    <div>
                        {{ $equipmentsPaginator->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
