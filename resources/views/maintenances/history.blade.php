@extends('layouts.staradmin')

@section('title', 'Riwayat Pasien')

@section('content_header')
<div class="row align-items-center mb-4">
    <div class="col-sm-6">
        <h2 class="h2 text-dark font-weight-bold mb-1">
            <i class="mdi mdi-history text-primary me-2"></i> Riwayat Berobat Pasien
        </h2>
        <p class="text-muted mb-0" style="font-size: 1.05rem;">
            Menampilkan catatan medis untuk: <strong class="text-primary">{{ $equipment->merk }}</strong> (No. RM: {{ $equipment->serial_number }})@if($equipment->lantai) | Lantai: <strong>Lantai {{ $equipment->lantai }}</strong>@endif
        </p>
    </div>
    <div class="col-sm-8 text-sm-end mt-3 mt-sm-0">
        <a href="{{ route('maintenances.index') }}" class="btn btn-outline-dark fw-bold px-3 py-2 me-1 shadow-sm" style="font-size: 1rem;">
            <i class="mdi mdi-arrow-left me-1 fs-5"></i> Kembali
        </a>
        <a href="{{ route('maintenances.qr', $equipment->serial_number) }}" target="_blank" class="btn btn-warning fw-bold text-dark px-3 py-2 me-1 shadow-sm" style="font-size: 1rem;">
            <i class="mdi mdi-qrcode-scan me-1 fs-5"></i> Cetak QR Kartu
        </a>
        <a href="{{ route('maintenances.export') }}" class="btn btn-success fw-bold text-white px-3 py-2 me-1 shadow-sm" style="font-size: 1rem;">
            <i class="mdi mdi-microsoft-excel me-1 fs-5"></i> .CSV
        </a>
        @if(auth()->user()->role !== 'viewer')
        <button class="btn btn-primary fw-bold text-white px-3 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addMaintenanceModal" style="font-size: 1rem;">
            <i class="mdi mdi-file-document-plus-outline me-1 fs-5"></i> Tulis Riwayat Pasien
        </button>
        @endif
    </div>
</div>
@stop

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        @if(session('success'))
            <div class="alert alert-success d-flex align-items-center fw-bold w-100 shadow-sm" style="font-size: 1.1rem;">
                <i class="mdi mdi-check-circle-outline fs-3 me-3"></i> {{ session('success') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger w-100 shadow-sm">
                <div class="d-flex align-items-center mb-2">
                    <i class="mdi mdi-alert-circle fs-3 me-2"></i> <strong style="font-size: 1.1rem;">Laporan Terhenti Akibat Kesalahan Input!</strong>
                </div>
                <ul class="mb-0 mt-1" style="font-size: 1.05rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close position-absolute top-50 end-0 translate-middle-y me-3" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card shadow-sm border-0" style="border-top: 4px solid #DC3545 !important;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title fw-bold text-danger mb-0" style="font-size: 1.25rem;">DAFTAR REKAM MEDIS & KUNJUNGAN PASIEN</h4>
                    <form action="" method="GET" class="w-25">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="mdi mdi-magnify text-muted fs-4"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0 bg-light fw-bold" placeholder="Cari didalam riwayat...">
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped border-top">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th class="text-white text-center" style="width: 50px;">NO</th>
                                <th class="text-white">PASIEN</th>
                                <th class="text-white">KLASIFIKASI & WAKTU PEMERIKSAAN</th>
                                <th class="text-white" style="width: 35%;">DOKTER / TENAGA MEDIS & URAIAN TINDAKAN</th>
                                <th class="text-white text-end">AKSES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($maintenances as $key => $mnt)
                            @php
                                $tglBerikutnya = \Carbon\Carbon::parse($mnt->tanggal_jadwal_berikutnya);
                                $isOverdue = $tglBerikutnya->isPast();
                                $isWarning = $tglBerikutnya->diffInDays(\Carbon\Carbon::now(), false) >= -30 && !$isOverdue;
                            @endphp
                            <tr>
                                <td class="text-center fw-bold">{{ $maintenances->firstItem() + $key }}</td>
                                <td>
                                    <h5 class="fw-bold text-primary mb-1" style="font-size: 1.15rem;">{{ $mnt->equipment->merk }}</h5>
                                    <div class="text-dark fw-bold mb-1" style="font-size: 1rem;">Kategori: {{ $mnt->equipment->type }}</div>
                                    @if($mnt->equipment->lantai)
                                        <div class="mb-1 text-muted" style="font-size: 0.92rem;"><i class="mdi mdi-layers-outline text-info me-1"></i> Lantai: <strong>Lantai {{ $mnt->equipment->lantai }}</strong></div>
                                    @endif
                                    <div><span class="badge bg-light text-dark border px-2 py-1"><i class="mdi mdi-barcode me-1"></i> No. RM: {{ $mnt->equipment->serial_number }}</span></div>
                                </td>
                                <td>
                                    <div class="mb-2">
                                        @if($mnt->jenis_pemeliharaan == 'Preventif')
                                            <span class="badge bg-success text-white shadow-sm px-3 py-2 fw-bold" style="font-size: 0.95rem;"><i class="mdi mdi-shield-check me-1"></i> Rutin / Kontrol</span>
                                        @elseif($mnt->jenis_pemeliharaan == 'Pemindahan Poli')
                                            <span class="badge bg-info text-white shadow-sm px-3 py-2 fw-bold" style="font-size: 0.95rem;"><i class="mdi mdi-swap-horizontal me-1"></i> Rujukan / Pindah Poli</span>
                                        @else
                                            <span class="badge bg-warning text-dark border border-dark shadow-sm px-3 py-2 fw-bold" style="font-size: 0.95rem;"><i class="mdi mdi-tools me-1"></i> Darurat / UGD</span>
                                        @endif
                                    </div>
                                    <div class="text-dark mb-1" style="font-size: 1.05rem;"><i class="mdi mdi-calendar-check text-success fs-5 me-1"></i> Tanggal Kunjungan: <b>{{ \Carbon\Carbon::parse($mnt->tanggal_pelaksanaan)->translatedFormat('d F Y') }}</b></div>
                                    <div class="text-dark" style="font-size: 1.05rem;">
                                        <i class="mdi mdi-calendar-clock text-danger fs-5 me-1"></i> Rencana Kontrol:
                                        <span class="fw-bold {{ $isOverdue ? 'text-danger' : ($isWarning ? 'text-warning' : 'text-primary') }}">
                                            {{ $tglBerikutnya->translatedFormat('d F Y') }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <h6 class="fw-bold text-dark mb-2" style="font-size: 1.05rem;"><i class="mdi mdi-doctor text-muted me-1"></i> {{ $mnt->petugas }}</h6>
                                    @php
                                        $borderClass = 'border-secondary';
                                        if ($mnt->jenis_pemeliharaan == 'Preventif') {
                                            $borderClass = 'border-success';
                                        } elseif ($mnt->jenis_pemeliharaan == 'Pemindahan Poli') {
                                            $borderClass = 'border-info';
                                        }
                                    @endphp
                                    <div class="p-3 bg-light border-start border-3 {{ $borderClass }} rounded text-dark" style="font-size: 0.95rem; font-style: italic; line-height: 1.5;">
                                        "{{ Str::limit($mnt->tindakan_hasil, 140) }}"
                                    </div>
                                    <div class="mt-2 d-flex flex-wrap gap-2">
                                        <span class="badge bg-light text-dark border" style="font-size: 0.85rem;"><i class="mdi mdi-clipboard-pulse text-primary me-1"></i> Diagnosa: <b>{{ $mnt->diagnosa_gejala ?: '-' }}</b></span>
                                        <span class="badge bg-light text-dark border" style="font-size: 0.85rem;"><i class="mdi mdi-map-marker text-danger me-1"></i> Lokasi: <b>{{ $mnt->lokasi_rawat ?: '-' }}</b></span>
                                        <span class="badge bg-light text-dark border" style="font-size: 0.85rem;">
                                              <i class="mdi mdi-heart-pulse text-success me-1"></i> 
                                              Kondisi: <b>
                                              @if($mnt->kondisi_klinis == 'Baik' || $mnt->kondisi_klinis == 'Stabil EWS')
                                                  <span class="text-success fw-bold">STABIL EWS</span>
                                              @elseif($mnt->kondisi_klinis == 'Rusak Ringan' || $mnt->kondisi_klinis == 'Stabil perlu observasi rutin EWS' || $mnt->kondisi_klinis == 'Perlu pemantauan khusus EWS')
                                                  <span class="text-warning fw-bold">OBSERVASI EWS</span>
                                              @elseif($mnt->kondisi_klinis == 'Perlu pemantauan ketat EWS')
                                                  <span class="fw-bold" style="color: #fd7e14;">PEMANTAUAN KETAT EWS</span>
                                              @elseif($mnt->kondisi_klinis == 'Rusak Berat' || $mnt->kondisi_klinis == 'Intensif ESW' || $mnt->kondisi_klinis == 'Intensif EWS')
                                                  <span class="text-danger fw-bold">INTENSIF EWS</span>
                                              @else
                                                  {{ $mnt->kondisi_klinis ?: '-' }}
                                              @endif
                                              </b>
                                        </span>
                                        <span class="badge bg-light text-dark border" style="font-size: 0.85rem;">
                                             <i class="mdi mdi-wallet text-info me-1"></i> 
                                             Bayar: <b>
                                             @if($mnt->metode_pembayaran == 'Milik RS')
                                                 BPJS
                                             @elseif($mnt->metode_pembayaran == 'KSO')
                                                 Asuransi
                                             @elseif($mnt->metode_pembayaran == 'Hibah')
                                                 Umum
                                             @else
                                                 -
                                             @endif
                                             </b>
                                        </span>
                                    </div>
                                </td>
                                <td class="text-end align-middle">
                                    @if(auth()->user()->role !== 'viewer')
                                    <div class="btn-group">
                                        <button class="btn btn-primary btn-sm px-3 shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#editMaintenanceModal{{ $mnt->id }}" title="Perbarui Jurnal">
                                            <i class="mdi mdi-pencil fs-5"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm px-3 shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#deleteMaintenanceModal{{ $mnt->id }}" title="Buang Baris">
                                            <i class="mdi mdi-trash-can fs-5"></i>
                                        </button>
                                    </div>
                                    @else
                                    <span class="badge bg-light text-muted border"><i class="mdi mdi-eye me-1"></i> View Only</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="mdi mdi-clipboard-text-clock-outline text-muted" style="font-size: 4rem;"></i>
                                    <h4 class="mt-3 text-dark fw-bold">Belum Ada Catatan Riwayat Berobat</h4>
                                    <p class="text-muted" style="font-size:1.1rem;">Mulai catat kunjungan pasien dan riwayat berobat untuk memantau perkembangan kesehatan pasien.</p>
                                    <button class="btn btn-primary px-4 py-2 text-white fw-bold mt-2" data-bs-toggle="modal" data-bs-target="#addMaintenanceModal">
                                        <i class="mdi mdi-plus-circle me-1"></i> Mulai Membuat Catatan
                                    </button>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-center">
                    {{ $maintenances->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Laporan -->
<div class="modal fade" id="addMaintenanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary px-4 py-3">
                <h5 class="modal-title fw-bold text-white fs-4"><i class="mdi mdi-plus-box-multiple-outline me-2"></i> Register Tindakan Pasien Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('maintenances.store') }}" method="POST">
                @csrf
                <div class="modal-body px-4 py-4 bg-light">
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Pasien Target <span class="text-danger">*</span></label>
                            <select name="equipment_id" class="form-select form-select-lg bg-white fw-bold text-dark" required>
                                <option value="" disabled>-- Pilih Pasien --</option>
                                @foreach($equipments as $eq)
                                    <option value="{{ $eq->id }}" {{ $equipment->id == $eq->id ? 'selected' : '' }}
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
                            <input type="text" name="diagnosa_gejala" id="hist_add_diagnosa_gejala" class="form-control form-control-lg bg-white" placeholder="Contoh: Infeksi Saluran Pernapasan">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Ruang Rawat / Lokasi Saat Ini</label>
                            <input type="text" name="lokasi_rawat" id="hist_add_lokasi_rawat" class="form-control form-control-lg bg-white" placeholder="Contoh: Poli Dalam / Gedung A">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Status Kondisi Klinis Saat Ini</label>
                            <select name="kondisi_klinis" id="hist_add_kondisi_klinis" class="form-select form-select-lg bg-white fw-bold text-dark">
                                <option value="Stabil EWS">Stabil EWS (Hijau)</option>
                                <option value="Stabil perlu observasi rutin EWS">Stabil perlu observasi rutin EWS (Kuning)</option>
                                <option value="Perlu pemantauan khusus EWS">Perlu pemantauan khusus EWS (Kuning)</option>
                                <option value="Perlu pemantauan ketat EWS">Perlu pemantauan ketat EWS (Orange)</option>
                                <option value="Intensif ESW">Intensif ESW (Merah)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Metode Pembayaran Saat Ini</label>
                            <select name="metode_pembayaran" id="hist_add_metode_pembayaran" class="form-select form-select-lg bg-white fw-bold text-dark">
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
                    <button type="submit" class="btn btn-primary text-white fw-bold px-4"><i class="mdi mdi-content-save me-1"></i> TAMBAH CATATAN RIWAYAT</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const patientSelect = document.querySelector('#addMaintenanceModal select[name="equipment_id"]');
        
        function populateFields() {
            if (!patientSelect) return;
            const selectedOption = patientSelect.options[patientSelect.selectedIndex];
            if (!selectedOption) return;
            const diagnosis = selectedOption.getAttribute('data-diagnosis') || '';
            const lokasi = selectedOption.getAttribute('data-lokasi') || '';
            const kondisi = selectedOption.getAttribute('data-kondisi') || 'Baik';
            const pembayaran = selectedOption.getAttribute('data-pembayaran') || 'Milik RS';

            document.getElementById('hist_add_diagnosa_gejala').value = diagnosis;
            document.getElementById('hist_add_lokasi_rawat').value = lokasi;
            document.getElementById('hist_add_kondisi_klinis').value = kondisi;
            document.getElementById('hist_add_metode_pembayaran').value = pembayaran;
        }

        if (patientSelect) {
            patientSelect.addEventListener('change', populateFields);
            populateFields(); // Trigger populate on load
        }
    });
</script>

<!-- Modals for Edit & Delete -->
@foreach($maintenances as $mnt)
<!-- Modal Edit -->
<div class="modal fade" id="editMaintenanceModal{{ $mnt->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark px-4 py-3">
                <h5 class="modal-title fw-bold text-white fs-4"><i class="mdi mdi-pencil-box-outline me-2"></i> Ubah Catatan Riwayat Tindakan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('maintenances.update', $mnt->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body px-4 py-4 bg-light">
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Pasien Target</label>
                            <select name="equipment_id" class="form-select form-select-lg disabled fw-bold text-dark" required>
                                @foreach($equipments as $eq)
                                    <option value="{{ $eq->id }}" {{ $eq->id == $mnt->equipment_id ? 'selected' : '' }}>
                                        {{ $eq->merk }} (No. RM: {{ $eq->serial_number }})@if($eq->lantai) - Lantai {{ $eq->lantai }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Kategori Tindakan</label>
                            <select name="jenis_pemeliharaan" class="form-select form-select-lg fw-bold text-dark" required>
                                <option value="Preventif" {{ $mnt->jenis_pemeliharaan == 'Preventif' ? 'selected' : '' }}>Rutin / Kontrol (Pencegahan)</option>
                                <option value="Pemindahan Poli" {{ $mnt->jenis_pemeliharaan == 'Pemindahan Poli' ? 'selected' : '' }}>Rujukan / Pindah Poli</option>
                                <option value="Korektif" {{ $mnt->jenis_pemeliharaan == 'Korektif' ? 'selected' : '' }}>Darurat / UGD (Tindakan Medis)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Dokter / Tenaga Medis</label>
                            <input type="text" name="petugas" class="form-control form-control-lg fw-bold" value="{{ $mnt->petugas }}" required list="doctors_list">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Tanggal Pemeriksaan / Tindakan</label>
                            <input type="date" name="tanggal_pelaksanaan" class="form-control form-control-lg fw-bold" value="{{ $mnt->tanggal_pelaksanaan }}" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Rencana Kontrol Selanjutnya</label>
                            <input type="date" name="tanggal_jadwal_berikutnya" class="form-control form-control-lg border-danger fw-bold text-danger" value="{{ $mnt->tanggal_jadwal_berikutnya }}" required>
                        </div>

                        <!-- 4 New Clinical and Billing Fields for Edit -->
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Diagnosa Utama / Gejala</label>
                            <input type="text" name="diagnosa_gejala" class="form-control form-control-lg bg-white fw-bold" value="{{ $mnt->diagnosa_gejala }}" placeholder="Contoh: Infeksi Saluran Pernapasan">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Ruang Rawat / Lokasi</label>
                            <input type="text" name="lokasi_rawat" class="form-control form-control-lg bg-white fw-bold" value="{{ $mnt->lokasi_rawat }}" placeholder="Contoh: Poli Dalam / Gedung A">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Status Kondisi Klinis</label>
                            <select name="kondisi_klinis" class="form-select form-select-lg bg-white fw-bold text-dark">
                                <option value="Stabil EWS" {{ $mnt->kondisi_klinis == 'Stabil EWS' || $mnt->kondisi_klinis == 'Baik' ? 'selected' : '' }}>Stabil EWS (Hijau)</option>
                                <option value="Stabil perlu observasi rutin EWS" {{ $mnt->kondisi_klinis == 'Stabil perlu observasi rutin EWS' ? 'selected' : '' }}>Stabil perlu observasi rutin EWS (Kuning)</option>
                                <option value="Perlu pemantauan khusus EWS" {{ $mnt->kondisi_klinis == 'Perlu pemantauan khusus EWS' || $mnt->kondisi_klinis == 'Rusak Ringan' ? 'selected' : '' }}>Perlu pemantauan khusus EWS (Kuning)</option>
                                <option value="Perlu pemantauan ketat EWS" {{ $mnt->kondisi_klinis == 'Perlu pemantauan ketat EWS' ? 'selected' : '' }}>Perlu pemantauan ketat EWS (Orange)</option>
                                <option value="Intensif ESW" {{ $mnt->kondisi_klinis == 'Intensif ESW' || $mnt->kondisi_klinis == 'Intensif EWS' || $mnt->kondisi_klinis == 'Rusak Berat' ? 'selected' : '' }}>Intensif ESW (Merah)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Metode Pembayaran</label>
                            <select name="metode_pembayaran" class="form-select form-select-lg bg-white fw-bold text-dark">
                                <option value="Milik RS" {{ $mnt->metode_pembayaran == 'Milik RS' ? 'selected' : '' }}>BPJS Kesehatan</option>
                                <option value="KSO" {{ $mnt->metode_pembayaran == 'KSO' ? 'selected' : '' }}>Asuransi Swasta</option>
                                <option value="Hibah" {{ $mnt->metode_pembayaran == 'Hibah' ? 'selected' : '' }}>Umum / Mandiri</option>
                            </select>
                        </div>

                        <div class="col-md-12 mb-2">
                            <label class="form-label text-dark fw-bold fs-5">Catatan Handover</label>
                            <textarea name="tindakan_hasil" class="form-control fw-bold" rows="5" required>{{ $mnt->tindakan_hasil }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white px-4 py-3">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">JANGAN UBAH</button>
                    <button type="submit" class="btn btn-dark fw-bold px-4"><i class="mdi mdi-content-save-all me-1"></i> SIMPAN PERUBAHAN</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus -->
<div class="modal fade" id="deleteMaintenanceModal{{ $mnt->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger px-4 py-3">
                <h5 class="modal-title fw-bold text-white fs-4"><i class="mdi mdi-alert-octagon me-2"></i> Konfirmasi Hapus Data</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-5 bg-light">
                <i class="mdi mdi-comment-remove-outline text-danger mb-3" style="font-size: 5rem; display:block;"></i>
                <h3 class="text-dark fw-bold mb-3">Hapus Catatan Riwayat?</h3>
                <p class="text-dark" style="font-size: 1.15rem;">Anda yakin ingin menghapus catatan pemeriksaan oleh dokter <span class="fw-bold text-primary">{{ $mnt->petugas }}</span> (Tanggal Kunjungan: <b>{{ \Carbon\Carbon::parse($mnt->tanggal_pelaksanaan)->translatedFormat('d M Y') }}</b>)? Data yang dihapus tidak dapat dipulihkan kembali.</p>
            </div>
            <div class="modal-footer bg-white px-4 py-3 d-flex justify-content-between">
                <button type="button" class="btn btn-light text-dark fw-bold w-45 px-4" data-bs-dismiss="modal">BATAL</button>
                <form action="{{ route('maintenances.destroy', $mnt->id) }}" method="POST" class="w-45">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger text-white fw-bold w-100"><i class="mdi mdi-delete-variant me-1"></i> KONFIRMASIKAN HAPUS</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

@php
    $doctorsList = \App\Models\Doctor::orderBy('name')->get();
@endphp
<datalist id="doctors_list">
    @foreach($doctorsList as $doc)
        <option value="{{ $doc->name }}">{{ $doc->ksm }}</option>
    @endforeach
</datalist>
@stop
