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
                    <button type="button" id="btnToggleEdit" class="btn btn-primary btn-sm fw-bold px-3 py-2 text-white shadow-sm">
                        <i class="mdi mdi-pencil me-1"></i> Ubah Data
                    </button>
                </div>

                <!-- VIEW MODE -->
                <div id="viewMode">
                    <div class="row">
                        <!-- Group 1: General Info -->
                        <div class="col-md-6 mb-3">
                            <label class="text-muted fw-bold small d-block mb-1"><i class="mdi mdi-calendar text-primary me-1"></i> Tanggal Registrasi / Masuk RS</label>
                            <div class="p-3 bg-light rounded border text-dark fw-bold">
                                {{ $equipment->registered_date ?: '-' }}
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted fw-bold small d-block mb-1"><i class="mdi mdi-clock-outline text-primary me-1"></i> LOS (Length of Stay) Aktual</label>
                            <div class="p-3 bg-light rounded border text-dark fw-bold">
                                {{ $equipment->los_aktual ?: '-' }}
                            </div>
                        </div>

                        <!-- Group 2: Doctors -->
                        <div class="col-md-6 mb-3">
                            <label class="text-muted fw-bold small d-block mb-1"><i class="mdi mdi-doctor text-success me-1"></i> DPJP Utama</label>
                            <div class="p-3 bg-light rounded border text-dark fw-bold">
                                {{ $equipment->dpjp_utama ?: '-' }}
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted fw-bold small d-block mb-1"><i class="mdi mdi-account-group text-success me-1"></i> DPJP Raber (Rawat Bersama)</label>
                            <div class="p-3 bg-light rounded border text-dark fw-bold">
                                {{ $equipment->dpjp_raber ?: '-' }}
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted fw-bold small d-block mb-1"><i class="mdi mdi-doctor text-info me-1"></i> Dokter Konsul</label>
                            <div class="p-3 bg-light rounded border text-dark fw-bold">
                                {{ $equipment->dokter_konsul ?: '-' }}
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted fw-bold small d-block mb-1"><i class="mdi mdi-clipboard-check text-info me-1"></i> Visit DPJP</label>
                            <div class="p-3 bg-light rounded border text-dark fw-bold">
                                {{ $equipment->visit_dpjp ?: '-' }}
                            </div>
                        </div>

                        <!-- Group 3: Nursing & Clinical Score -->
                        <div class="col-md-6 mb-3">
                            <label class="text-muted fw-bold small d-block mb-1"><i class="mdi mdi-account-star text-warning me-1"></i> NPJA</label>
                            <div class="p-3 bg-light rounded border text-dark fw-bold">
                                {{ $equipment->npja ?: '-' }}
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted fw-bold small d-block mb-1"><i class="mdi mdi-heart-pulse text-danger me-1"></i> EWS (Early Warning Score)</label>
                            <div class="p-3 bg-light rounded border text-dark fw-bold">
                                {{ $equipment->ews ?: '-' }}
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted fw-bold small d-block mb-1"><i class="mdi mdi-human text-warning me-1"></i> Tingkat Ketergantungan</label>
                            <div class="p-3 bg-light rounded border text-dark fw-bold">
                                {{ $equipment->tingkat_ketergantungan ?: '-' }}
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted fw-bold small d-block mb-1"><i class="mdi mdi-account-network text-warning me-1"></i> Ners yang Bertugas</label>
                            <div class="p-3 bg-light rounded border text-dark fw-bold">
                                {{ $equipment->ners_bertugas ?: '-' }}
                            </div>
                        </div>

                        <!-- Group 4: Treatment & Planning (Large Fields) -->
                        <div class="col-md-12 mb-3">
                            <label class="text-muted fw-bold small d-block mb-1"><i class="mdi mdi-clipboard-text text-dark me-1"></i> Diagnosis Medis Saat Ini</label>
                            <div class="p-3 bg-light rounded border text-dark">
                                {{ $equipment->type ?: '-' }}
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="text-muted fw-bold small d-block mb-1"><i class="mdi mdi-lightbulb-on text-primary me-1"></i> Planning Pasien</label>
                            <div class="p-3 bg-light rounded border text-dark" style="min-height: 80px; white-space: pre-wrap;">{{ $equipment->planning_pasien ?: '-' }}</div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="text-muted fw-bold small d-block mb-1"><i class="mdi mdi-logout text-success me-1"></i> Rencana Pulang</label>
                            <div class="p-3 bg-light rounded border text-dark" style="min-height: 60px; white-space: pre-wrap;">{{ $equipment->rencana_pulang ?: '-' }}</div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="text-muted fw-bold small d-block mb-1"><i class="mdi mdi-needle text-danger me-1"></i> Penggunaan Alkes & Alat Invasif</label>
                            <div class="p-3 bg-light rounded border text-dark" style="min-height: 80px; white-space: pre-wrap;">{{ $equipment->alkes_invasif ?: '-' }}</div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="text-muted fw-bold small d-block mb-1"><i class="mdi mdi-medical-bag text-primary me-1"></i> Tindakan / Terapi Medis</label>
                            <div class="p-3 bg-light rounded border text-dark" style="min-height: 80px; white-space: pre-wrap;">{{ $equipment->tindakan_detail ?: '-' }}</div>
                        </div>
                    </div>
                </div>

                <!-- EDIT FORM MODE -->
                <div id="editMode" style="display: none;">
                    <form action="{{ route('maintenances.update_patient_detail', $equipment->serial_number) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <!-- Group 1: General Info -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark fw-bold small"><i class="mdi mdi-calendar text-primary me-1"></i> Tanggal Registrasi / Masuk RS</label>
                                <input type="text" name="registered_date" class="form-control" value="{{ $equipment->registered_date }}" placeholder="Contoh: 15 Juni 2026 atau 2026-06-15">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark fw-bold small"><i class="mdi mdi-clock-outline text-primary me-1"></i> LOS (Length of Stay) Aktual</label>
                                <input type="text" name="los_aktual" class="form-control" value="{{ $equipment->los_aktual }}" placeholder="Contoh: 4 Hari">
                            </div>

                            <!-- Group 2: Doctors -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark fw-bold small"><i class="mdi mdi-doctor text-success me-1"></i> DPJP Utama</label>
                                <input type="text" name="dpjp_utama" class="form-control" value="{{ $equipment->dpjp_utama }}" placeholder="Nama DPJP Utama">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark fw-bold small"><i class="mdi mdi-account-group text-success me-1"></i> DPJP Raber (Rawat Bersama)</label>
                                <input type="text" name="dpjp_raber" class="form-control" value="{{ $equipment->dpjp_raber }}" placeholder="Nama Dokter Raber">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark fw-bold small"><i class="mdi mdi-doctor text-info me-1"></i> Dokter Konsul</label>
                                <input type="text" name="dokter_konsul" class="form-control" value="{{ $equipment->dokter_konsul }}" placeholder="Nama Dokter Konsul">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark fw-bold small"><i class="mdi mdi-clipboard-check text-info me-1"></i> Visit DPJP</label>
                                <input type="text" name="visit_dpjp" class="form-control" value="{{ $equipment->visit_dpjp }}" placeholder="Contoh: Sudah (Pukul 09:00)">
                            </div>

                            <!-- Group 3: Nursing & Clinical Score -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark fw-bold small"><i class="mdi mdi-account-star text-warning me-1"></i> NPJA</label>
                                <input type="text" name="npja" class="form-control" value="{{ $equipment->npja }}" placeholder="Nama NPJA">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark fw-bold small"><i class="mdi mdi-heart-pulse text-danger me-1"></i> EWS (Early Warning Score)</label>
                                <input type="text" name="ews" class="form-control" value="{{ $equipment->ews }}" placeholder="Contoh: Skor 2 (Stabil)">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark fw-bold small"><i class="mdi mdi-human text-warning me-1"></i> Tingkat Ketergantungan</label>
                                <input type="text" name="tingkat_ketergantungan" class="form-control" value="{{ $equipment->tingkat_ketergantungan }}" placeholder="Contoh: Ketergantungan Sedang">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-dark fw-bold small"><i class="mdi mdi-account-network text-warning me-1"></i> Ners yang Bertugas</label>
                                <input type="text" name="ners_bertugas" class="form-control" value="{{ $equipment->ners_bertugas }}" placeholder="Nama-nama Ners">
                            </div>

                            <!-- Group 4: Treatment & Planning (Large Fields) -->
                            <div class="col-md-12 mb-3">
                                <label class="form-label text-dark fw-bold small"><i class="mdi mdi-clipboard-text text-dark me-1"></i> Diagnosis Medis Saat Ini</label>
                                <input type="text" name="type" class="form-control" value="{{ $equipment->type }}" placeholder="Diagnosis Medis">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label text-dark fw-bold small"><i class="mdi mdi-lightbulb-on text-primary me-1"></i> Planning Pasien</label>
                                <textarea name="planning_pasien" class="form-control" rows="3" placeholder="Rencana penanganan medis...">{{ $equipment->planning_pasien }}</textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label text-dark fw-bold small"><i class="mdi mdi-logout text-success me-1"></i> Rencana Pulang</label>
                                <textarea name="rencana_pulang" class="form-control" rows="2" placeholder="Rencana kepulangan pasien...">{{ $equipment->rencana_pulang }}</textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label text-dark fw-bold small"><i class="mdi mdi-needle text-danger me-1"></i> Penggunaan Alkes & Alat Invasif</label>
                                <textarea name="alkes_invasif" class="form-control" rows="3" placeholder="Contoh: Infus RL, Kateter Urine, dll...">{{ $equipment->alkes_invasif }}</textarea>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label text-dark fw-bold small"><i class="mdi mdi-medical-bag text-primary me-1"></i> Tindakan / Terapi Medis</label>
                                <textarea name="tindakan_detail" class="form-control" rows="3" placeholder="Terapi obat, pembedahan, atau tindakan khusus lainnya...">{{ $equipment->tindakan_detail }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" id="btnCancelEdit" class="btn btn-light fw-bold px-4">Batal</button>
                            <button type="submit" class="btn btn-success text-white fw-bold px-4">
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
        const viewMode = document.getElementById('viewMode');
        const editMode = document.getElementById('editMode');
        const btnToggleEdit = document.getElementById('btnToggleEdit');
        const btnCancelEdit = document.getElementById('btnCancelEdit');

        btnToggleEdit.addEventListener('click', function() {
            if (viewMode.style.display === 'none') {
                // Currently in edit mode, cancel it
                showViewMode();
            } else {
                // Switch to edit mode
                showEditMode();
            }
        });

        btnCancelEdit.addEventListener('click', function() {
            showViewMode();
        });

        function showViewMode() {
            viewMode.style.display = 'block';
            editMode.style.display = 'none';
            btnToggleEdit.innerHTML = '<i class="mdi mdi-pencil me-1"></i> Ubah Data';
            btnToggleEdit.className = 'btn btn-primary btn-sm fw-bold px-3 py-2 text-white shadow-sm';
        }

        function showEditMode() {
            viewMode.style.display = 'none';
            editMode.style.display = 'block';
            btnToggleEdit.innerHTML = '<i class="mdi mdi-eye me-1"></i> Lihat Data';
            btnToggleEdit.className = 'btn btn-outline-primary btn-sm fw-bold px-3 py-2 shadow-sm';
        }
    });
</script>
@stop
