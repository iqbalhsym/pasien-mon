@extends('layouts.staradmin')

@section('title', 'Jadwal Kontrol Pasien')

@section('content_header')
<div class="row align-items-center mb-4">
    <div class="col-sm-8">
        <h2 class="h2 text-dark font-weight-bold mb-1">
            <i class="mdi mdi-calendar-clock text-primary me-2"></i> Jadwal Kontrol & Konsultasi Pasien
        </h2>
        <p class="text-muted mb-0" style="font-size: 1.05rem;">Pantau rencana kunjungan kontrol rutin, konsultasi dokter spesialis, dan riwayat janji medis terdekat.</p>
    </div>
    <div class="col-12 mt-3 d-flex flex-wrap justify-content-end align-items-center gap-2">
        <a href="{{ route('calibrations.export') }}" class="btn btn-outline-success fw-semibold px-3 py-2">
            <i class="mdi mdi-file-export me-1"></i> Export CSV
        </a>
        @if(auth()->user()->role !== 'viewer')
        <button class="btn btn-outline-warning fw-semibold px-3 py-2" data-bs-toggle="modal" data-bs-target="#importCalibrationModal">
            <i class="mdi mdi-file-import me-1"></i> Import CSV
        </button>
        <button class="btn btn-primary fw-semibold text-white px-3 py-2" data-bs-toggle="modal" data-bs-target="#addCalibrationModal">
            <i class="mdi mdi-text-box-plus-outline me-1"></i> Input Kontrol Baru
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
                <i class="mdi mdi-check-decagram fs-3 me-3"></i> {{ session('success') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger w-100 shadow-sm">
                <div class="d-flex align-items-center mb-2">
                    <i class="mdi mdi-alert-circle fs-3 me-2"></i> <strong style="font-size: 1.1rem;">Gagal Menyimpan Jadwal Kontrol!</strong>
                </div>
                <ul class="mb-0 mt-1" style="font-size: 1.05rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close position-absolute top-50 end-0 translate-middle-y me-3" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card shadow-sm border-0" style="border-top: 4px solid #F59F00 !important;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title fw-bold text-warning mb-0" style="font-size: 1.25rem;">DAFTAR JADWAL KONTROL & KONSULTASI PASIEN</h4>
                    <form action="{{ route('calibrations.index') }}" method="GET" class="w-25">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="mdi mdi-magnify text-muted fs-4"></i></span>
                            <input type="text" name="search" class="form-control border-start-0 ps-0 bg-light fw-bold" placeholder="Cari nama pasien..." value="{{ request('search') }}">
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped border-top">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th class="text-white text-center" style="width: 50px;">NO</th>
                                <th class="text-white">PASIEN TARGET</th>
                                <th class="text-white">DOKTER / POLIKLINIK</th>
                                <th class="text-white">RENCANA JADWAL KONTROL</th>
                                <th class="text-white">RUJUKAN / REKOMENDASI</th>
                                <th class="text-white text-end">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($calibrations as $key => $cal)
                            @php
                                $tglBerikutnya = \Carbon\Carbon::parse($cal->tanggal_kalibrasi_berikutnya);
                                $isOverdue = $tglBerikutnya->isPast();
                                $isWarning = $tglBerikutnya->diffInDays(\Carbon\Carbon::now(), false) >= -30 && !$isOverdue;
                            @endphp
                            <tr>
                                <td class="text-center fw-bold">{{ $calibrations->firstItem() + $key }}</td>
                                <td>
                                    <h5 class="fw-bold text-primary mb-1" style="font-size: 1.15rem;">{{ $cal->equipment->merk }}</h5>
                                    <div class="text-dark fw-bold mb-1" style="font-size: 1rem;">Diagnosis: {{ $cal->equipment->type }}</div>
                                    <div><span class="badge bg-light text-dark border px-2 py-1"><i class="mdi mdi-barcode me-1"></i> No. RM: {{ $cal->equipment->serial_number }}</span></div>
                                </td>
                                <td>
                                    <h6 class="fw-bold text-dark" style="font-size: 1.05rem;">{{ $cal->penyedia_jasa }}</h6>
                                </td>
                                <td>
                                    <div class="mb-1 text-dark" style="font-size: 1.05rem;"><i class="mdi mdi-calendar-check text-success fs-5 me-1"></i> Terakhir Kontrol: <b>{{ \Carbon\Carbon::parse($cal->tanggal_kalibrasi)->translatedFormat('d M Y') }}</b></div>
                                    <div class="mb-1 text-dark" style="font-size: 1.05rem;">
                                        <i class="mdi mdi-calendar-clock text-warning fs-5 me-1"></i> Rencana Kontrol: 
                                        <span class="fw-bold {{ $isOverdue ? 'text-danger' : ($isWarning ? 'text-warning' : 'text-primary') }}">
                                            {{ $tglBerikutnya->translatedFormat('d M Y') }}
                                        </span>
                                    </div>
                                    <div class="mt-2">
                                        @if($isOverdue)
                                            <span class="badge bg-danger shadow-sm px-2 py-1"><i class="mdi mdi-alert"></i> Terlewat / Terlambat</span>
                                        @elseif($isWarning)
                                            <span class="badge bg-warning text-dark border border-dark shadow-sm px-2 py-1"><i class="mdi mdi-bell-ring"></i> Segera Kontrol</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($cal->hasMedia('calibrations'))
                                        <a href="{{ $cal->getFirstTemporaryUrl(now()->addHour(), 'calibrations') }}" target="_blank" class="btn btn-outline-success btn-sm fw-bold px-3 py-2 shadow-sm">
                                            <i class="mdi mdi-download me-1 fs-5"></i> Buka Rujukan
                                        </a>
                                    @elseif($cal->sertifikat)
                                        <a href="{{ asset('storage/' . $cal->sertifikat) }}" target="_blank" class="btn btn-outline-success btn-sm fw-bold px-3 py-2 shadow-sm">
                                            <i class="mdi mdi-download me-1 fs-5"></i> Buka File Lama
                                        </a>
                                    @else
                                        <div class="text-muted fw-bold p-2 bg-light border rounded"><i class="mdi mdi-close-octagon me-1 text-danger"></i> Tidak Ada Rujukan</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if(auth()->user()->role !== 'viewer')
                                    <div class="btn-group">
                                        <button class="btn btn-primary btn-sm px-3 shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#editCalibrationModal{{ $cal->id }}" title="Edit">
                                            <i class="mdi mdi-pencil fs-5"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm px-3 shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#deleteCalibrationModal{{ $cal->id }}" title="Hapus">
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
                                <td colspan="6" class="text-center py-5">
                                    <i class="mdi mdi-calendar-blank text-muted" style="font-size: 4rem;"></i>
                                    <h4 class="mt-3 text-dark fw-bold">Belum Ada Jadwal Kontrol Pasien</h4>
                                    <p class="text-muted" style="font-size:1.1rem;">Lacak masa berlaku tes dan janji temu medis di sini.</p>
                                    <button class="btn btn-primary px-4 py-2 text-white fw-bold mt-2" data-bs-toggle="modal" data-bs-target="#addCalibrationModal">
                                        <i class="mdi mdi-plus-circle me-1"></i> Jadwalkan Kontrol Baru
                                    </button>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 pt-3 border-top d-flex justify-content-center">
                    {{ $calibrations->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Kontrol -->
<div class="modal fade" id="addCalibrationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary px-4 py-3">
                <h5 class="modal-title fw-bold text-white fs-4"><i class="mdi mdi-calendar-plus me-2"></i> Jadwalkan Kontrol Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('calibrations.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body px-4 py-4 bg-light">
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Nama Pasien <span class="text-danger">*</span></label>
                            <select name="equipment_id" class="form-select form-select-lg bg-white" required>
                                <option value="" disabled selected>-- Pilih Pasien --</option>
                                @foreach($equipments as $eq)
                                    <option value="{{ $eq->id }}" class="fw-bold">{{ $eq->merk }} (No. RM: {{ $eq->serial_number }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Tanggal Pemeriksaan Terakhir <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_kalibrasi" class="form-control form-control-lg bg-white" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Rencana Kontrol Berikutnya <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_kalibrasi_berikutnya" class="form-control form-control-lg bg-white border-warning" required>
                            <small class="text-muted fw-bold">Pengingat peringatan warna dihitung dari tanggal ini.</small>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Dokter Spesialis / Poliklinik <span class="text-danger">*</span></label>
                            <input type="text" name="penyedia_jasa" class="form-control form-control-lg bg-white" required placeholder="Contoh: dr. Budi Santoso, Sp.PD (Poli Dalam)">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Unggah Surat Rujukan/Resep (PDF/Gambar)</label>
                            <input type="file" name="sertifikat" class="form-control form-control-lg bg-white border-primary" accept=".pdf,image/*">
                            <small class="text-danger fw-bold d-block mt-1"><i class="mdi mdi-alert-circle me-1"></i> Maksimal ukuran file: 2 MB</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white px-4 py-3">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn btn-primary text-white fw-bold px-4"><i class="mdi mdi-content-save me-1"></i> SIMPAN JADWAL</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modals for Edit & Delete -->
@foreach($calibrations as $cal)
<!-- Modal Edit -->
<div class="modal fade" id="editCalibrationModal{{ $cal->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark px-4 py-3">
                <h5 class="modal-title fw-bold text-white fs-4"><i class="mdi mdi-pencil-box-outline me-2"></i> Perbaiki Jadwal Kontrol</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('calibrations.update', $cal->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body px-4 py-4 bg-light">
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Pasien Target</label>
                            <select name="equipment_id" class="form-select form-select-lg disabled text-dark fw-bold" required>
                                @foreach($equipments as $eq)
                                    <option value="{{ $eq->id }}" {{ $eq->id == $cal->equipment_id ? 'selected' : '' }}>
                                        {{ $eq->merk }} (No. RM: {{ $eq->serial_number }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Tanggal Pemeriksaan Terakhir</label>
                            <input type="date" name="tanggal_kalibrasi" class="form-control form-control-lg" value="{{ $cal->tanggal_kalibrasi }}" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Rencana Kontrol Berikutnya</label>
                            <input type="date" name="tanggal_kalibrasi_berikutnya" class="form-control form-control-lg border-warning fw-bold" value="{{ $cal->tanggal_kalibrasi_berikutnya }}" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Dokter Spesialis / Poliklinik</label>
                            <input type="text" name="penyedia_jasa" class="form-control form-control-lg fw-bold text-dark" value="{{ $cal->penyedia_jasa }}" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Timpa Surat Rujukan (Kosongkan jika tidak berubah)</label>
                            <input type="file" name="sertifikat" class="form-control form-control-lg" accept=".pdf,image/*">
                            <small class="text-danger fw-bold d-block mt-1"><i class="mdi mdi-alert-circle me-1"></i> Maksimal ukuran file: 2 MB</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white px-4 py-3">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">BATALKAN</button>
                    <button type="submit" class="btn btn-dark fw-bold px-4"><i class="mdi mdi-content-save me-1"></i> PERBARUI JADWAL</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus -->
<div class="modal fade" id="deleteCalibrationModal{{ $cal->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger px-4 py-3">
                <h5 class="modal-title fw-bold text-white fs-4"><i class="mdi mdi-alert-octagon me-2"></i> Konfirmasi Penghapusan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-5 bg-light">
                <i class="mdi mdi-calendar-remove text-danger mb-3" style="font-size: 5rem; display:block;"></i>
                <h3 class="text-dark fw-bold mb-3">Hapus Rencana Kontrol Ini?</h3>
                <p class="text-dark" style="font-size: 1.15rem;">Anda akan menghapus rencana kontrol pasien pada tanggal <b>{{ \Carbon\Carbon::parse($cal->tanggal_kalibrasi_berikutnya)->translatedFormat('d M Y') }}</b> oleh dokter <span class="fw-bold text-primary">{{ $cal->penyedia_jasa }}</span> secara permanen.</p>
            </div>
            <div class="modal-footer bg-white px-4 py-3 d-flex justify-content-between">
                <button type="button" class="btn btn-light fw-bold w-45 px-4" data-bs-dismiss="modal">TIDAK</button>
                <form action="{{ route('calibrations.destroy', $cal->id) }}" method="POST" class="w-45">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger fw-bold w-100 text-white"><i class="mdi mdi-trash-can me-1"></i> HAPUS JADWAL</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

@stop

{{-- Modal Import CSV Kontrol --}}
@if(auth()->user()->role !== 'viewer')
<div class="modal fade" id="importCalibrationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning px-4 py-3">
                <h5 class="modal-title fw-bold text-dark fs-4">
                    <i class="mdi mdi-file-import me-2"></i> Import Jadwal Kontrol Pasien
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('calibrations.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body px-4 py-4">
                    <div class="alert alert-info border-0 shadow-sm">
                        <strong><i class="mdi mdi-information-outline me-1"></i> Panduan Format CSV:</strong><br>
                        Kolom harus sesuai urutan hasil Export:<br>
                        <code>No &nbsp;|&nbsp; Nama Pasien &nbsp;|&nbsp; No. RM &nbsp;|&nbsp; Tanggal Pemeriksaan Terakhir &nbsp;|&nbsp; Dokter Pemeriksa &nbsp;|&nbsp; Rencana Kontrol Berikutnya</code>
                        <hr class="my-2">
                        <small class="text-muted">
                           <i class="mdi mdi-alert-circle-outline me-1"></i>
                           Data dicocokkan berdasarkan <strong>No. RM</strong>.
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold fs-5">Pilih File CSV <span class="text-danger">*</span></label>
                        <input type="file" name="file_csv" class="form-control form-control-lg" accept=".csv,.txt" required>
                        <small class="text-muted d-block mt-1">
                            Format tanggal: <code>DD/MM/YYYY</code> atau <code>YYYY-MM-DD</code>. Ukuran maks. <strong>2 MB</strong>.
                        </small>
                    </div>
                </div>
                <div class="modal-footer bg-white px-4 py-3">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn btn-warning fw-bold text-dark px-4">
                        <i class="mdi mdi-upload me-1"></i> PROSES IMPORT
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
