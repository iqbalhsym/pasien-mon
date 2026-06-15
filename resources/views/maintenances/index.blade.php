@extends('layouts.staradmin')

@section('title', 'Riwayat Pasien')

@section('content_header')
<div class="row align-items-center mb-4">
    <div class="col-sm-6">
        <h2 class="h2 text-dark font-weight-bold mb-1">
            <i class="mdi mdi-account-clock text-primary me-2"></i> Administrasi Pasien
        </h2>
        <p class="text-muted mb-0" style="font-size: 1.05rem;">Pencatatan riwayat berobat pasien, rekam medis, dan rencana kontrol selanjutnya.</p>
    </div>
    <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
        <a href="{{ route('maintenances.export') }}" class="btn btn-success fw-bold text-white px-4 py-2 me-2 shadow-sm" style="font-size: 1rem;">
            <i class="mdi mdi-microsoft-excel me-1 fs-5"></i> Tarik Data .CSV
        </a>
        @if(auth()->user()->role !== 'viewer')
        <button class="btn btn-primary fw-bold text-white px-4 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#addMaintenanceModal" style="font-size: 1rem;">
            <i class="mdi mdi-file-document-plus-outline me-1 fs-5"></i> Tulis Riwayat Pasien Baru
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
                    <h4 class="card-title fw-bold text-danger mb-0" style="font-size: 1.25rem;">CATATAN RIWAYAT BEROBAT PASIEN & RENCANA KONTROL</h4>
                    <form action="{{ route('maintenances.index') }}" method="GET" class="w-25">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="mdi mdi-magnify text-muted fs-4"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0 bg-light fw-bold" placeholder="Cari nama dokter, pasien, RM..." value="{{ request('search') }}">
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped border-top">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th class="text-white text-center" style="width: 50px;">NO</th>
                                <th class="text-white">NAMA PASIEN</th>
                                <th class="text-white">LOKASI & JUMLAH RIWAYAT BEROBAT</th>
                                <th class="text-white" style="width: 35%;">INFO KUNJUNGAN TERAKHIR & RENCANA KONTROL</th>
                                <th class="text-white text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($equipmentsPaginator as $key => $eq)
                            @php
                                $lastMaintenance = $eq->maintenances->first();
                                $tglBerikutnya = $lastMaintenance ? \Carbon\Carbon::parse($lastMaintenance->tanggal_jadwal_berikutnya) : null;
                                $isOverdue = $tglBerikutnya ? $tglBerikutnya->isPast() : false;
                                $isWarning = $tglBerikutnya ? ($tglBerikutnya->diffInDays(\Carbon\Carbon::now(), false) >= -30 && !$isOverdue) : false;
                            @endphp
                            <tr>
                                <td class="text-center fw-bold">{{ $equipmentsPaginator->firstItem() + $key }}</td>
                                <td>
                                    <h5 class="fw-bold text-primary mb-1" style="font-size: 1.15rem;">{{ $eq->merk }}</h5>
                                    <div class="text-dark fw-bold mb-1" style="font-size: 1rem;">Kategori: {{ $eq->type }}</div>
                                    <div><span class="badge bg-light text-dark border px-2 py-1"><i class="mdi mdi-barcode me-1"></i> No. RM: {{ $eq->serial_number }}</span></div>
                                </td>
                                <td>
                                    <div class="mb-1 text-dark fs-6"><i class="mdi mdi-map-marker text-danger me-1"></i> {{ $eq->lokasi ?: '-' }}</div>
                                    @if($eq->lantai)
                                        <div class="mb-1 text-muted" style="font-size: 0.92rem;"><i class="mdi mdi-layers-outline text-info me-1"></i> Lantai: <strong>Lantai {{ $eq->lantai }}</strong></div>
                                    @endif
                                    <div class="badge bg-info text-white shadow-sm px-3 py-2 fw-bold" style="font-size: 0.95rem;">
                                        <i class="mdi mdi-history me-1"></i> {{ $eq->maintenances_count }} Kunjungan Tercatat
                                    </div>
                                </td>
                                <td>
                                    @if($lastMaintenance)
                                        <div class="text-dark mb-1" style="font-size: 1.05rem;">
                                            <i class="mdi mdi-calendar-check text-success fs-5 me-1"></i> Kunjungan Terakhir: <b>{{ \Carbon\Carbon::parse($lastMaintenance->tanggal_pelaksanaan)->translatedFormat('d F Y') }}</b>
                                        </div>
                                        <div class="text-dark mb-2" style="font-size: 1.05rem;">
                                            <i class="mdi mdi-calendar-clock text-danger fs-5 me-1"></i> Rencana Kontrol Berikutnya:
                                            <span class="fw-bold {{ $isOverdue ? 'text-danger' : ($isWarning ? 'text-warning' : 'text-primary') }}">
                                                {{ $tglBerikutnya->translatedFormat('d F Y') }}
                                            </span>
                                        </div>
                                        @php
                                            $borderClass = 'border-secondary';
                                            if ($lastMaintenance->jenis_pemeliharaan == 'Preventif') {
                                                $borderClass = 'border-success';
                                            } elseif ($lastMaintenance->jenis_pemeliharaan == 'Pemindahan Poli') {
                                                $borderClass = 'border-info';
                                            }
                                        @endphp
                                        <div class="p-2 bg-light border-start border-3 {{ $borderClass }} rounded text-dark mt-2" style="font-size: 0.9rem; font-style: italic; line-height: 1.4;">
                                            <span class="fw-bold not-italic text-muted small d-block mb-1"><i class="mdi mdi-file-document-outline me-1"></i> Tindakan/Hasil Terakhir:</span>
                                            "{{ Str::limit($lastMaintenance->tindakan_hasil, 100) }}"
                                        </div>
                                    @else
                                        <div class="text-muted fst-italic"><i class="mdi mdi-alert-circle-outline me-1"></i> Belum ada riwayat berobat</div>
                                    @endif
                                </td>
                                <td class="text-center align-middle">
                                    <a href="{{ route('maintenances.history', $eq->serial_number) }}" class="btn btn-primary fw-bold px-3 py-2 text-white shadow-sm">
                                        Lihat Riwayat <i class="mdi mdi-arrow-right-bold ms-1"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="mdi mdi-text-box-search-outline text-muted" style="font-size: 4rem;"></i>
                                    <h4 class="mt-3 text-dark fw-bold">Data Pasien Tidak Ditemukan</h4>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-center">
                    {{ $equipmentsPaginator->links('pagination::bootstrap-5') }}
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
                            <input type="text" name="petugas" class="form-control form-control-lg bg-white" required placeholder="Sebutkan Nama Dokter / Perawat">
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
                    <button type="submit" class="btn btn-primary text-white fw-bold px-4"><i class="mdi mdi-content-save me-1"></i> TAMBAH CATATAN RIWAYAT</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const patientSelect = document.querySelector('select[name="equipment_id"]');
        if (patientSelect) {
            patientSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const diagnosis = selectedOption.getAttribute('data-diagnosis') || '';
                const lokasi = selectedOption.getAttribute('data-lokasi') || '';
                const kondisi = selectedOption.getAttribute('data-kondisi') || 'Baik';
                const pembayaran = selectedOption.getAttribute('data-pembayaran') || 'Milik RS';

                document.getElementById('add_diagnosa_gejala').value = diagnosis;
                document.getElementById('add_lokasi_rawat').value = lokasi;
                document.getElementById('add_kondisi_klinis').value = kondisi;
                document.getElementById('add_metode_pembayaran').value = pembayaran;
            });
        }
    });
</script>
@stop
