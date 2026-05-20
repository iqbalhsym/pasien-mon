@extends('layouts.staradmin')

@section('title', 'Manajemen Pasien')

@section('content_header')
<div class="row align-items-center mb-4">
    <div class="col-sm-8">
        <h2 class="h2 text-dark font-weight-bold mb-1">
            <i class="mdi mdi-account-multiple text-primary me-2"></i> Manajemen Pasien
        </h2>
        <p class="text-muted mb-0" style="font-size: 1.05rem;">Manajemen data pendaftaran, rekam medis, dan kondisi klinis terkini seluruh pasien.</p>
    </div>
    <div class="col-sm-4 text-sm-end mt-3 mt-sm-0">
        @if(auth()->user()->role !== 'viewer')
            <button class="btn btn-primary fw-bold px-4 py-2 shadow-sm" data-bs-toggle="modal"
                data-bs-target="#addEquipmentModal" style="font-size: 1rem;">
                <i class="mdi mdi-account-plus me-1 fs-5"></i> Registrasi Pasien Baru
            </button>
        @endif
    </div>
</div>
@stop

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        {{-- Alert Notifikasi --}}
        @if(session('success'))
            <div class="alert alert-success d-flex align-items-center fw-bold w-100 shadow-sm mb-3"
                style="font-size: 1.1rem;">
                <i class="mdi mdi-check-circle fs-3 me-3"></i> {{ session('success') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger w-100 shadow-sm mb-3">
                <div class="d-flex align-items-center mb-2">
                    <i class="mdi mdi-alert fs-3 me-2"></i> <strong style="font-size: 1.1rem;">Gagal Menyimpan Data Pasien!</strong>
                </div>
                <ul class="mb-0 mt-1" style="font-size: 1.05rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Panel Export & Import --}}
        @if(auth()->user()->role !== 'viewer')
            <div class="card mb-3 shadow-sm border-0">
                <div class="card-body py-3">
                    <div class="row align-items-center">
                        <div class="col-md-12 d-flex flex-wrap align-items-center">
                            <a href="{{ route('equipments.export') }}"
                                class="btn btn-outline-success btn-sm me-3 mb-2 mb-md-0">
                                <i class="mdi mdi-download me-1"></i> Export CSV
                            </a>
                            <div class="vr me-3 d-none d-md-block" style="height: 30px;"></div>
                            <form action="{{ route('equipments.import') }}" method="POST" enctype="multipart/form-data"
                                class="d-flex align-items-center flex-grow-1">
                                @csrf
                                <label class="me-2 mb-0 fw-bold text-muted d-none d-lg-block">Import CSV:</label>
                                <input type="file" name="file_csv" class="form-control form-control-sm me-2"
                                    style="max-width: 250px;" required>
                                <button type="submit" class="btn btn-dark btn-sm text-white px-3">
                                    <i class="mdi mdi-upload"></i> Jalankan Import
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Tabel Data --}}
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
            <div class="d-flex align-items-center">
                <h4 class="card-title fw-bold text-primary mb-0 me-3" style="font-size: 1.25rem;">DAFTAR PASIEN TERDAFTAR
                </h4>

                {{-- Dropdown Per Page --}}
                <div class="d-flex align-items-center bg-light border rounded px-2 py-1">
                    <small class="text-muted fw-bold me-2">Tampilkan:</small>
                    <select class="form-select form-select-sm border-0 bg-transparent fw-bold text-primary"
                        style="width: auto; cursor: pointer;" onchange="window.location.href = this.value">
                        @foreach([10, 25, 50, 100] as $value)
                            <option value="{{ request()->fullUrlWithQuery(['per_page' => $value]) }}"
                                @selected(request('per_page', 10) == $value)>
                                {{ $value }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Form Pencarian (tetap sama) --}}
            <form action="{{ route('equipments.index') }}" method="GET" class="col-12 col-md-4 mt-2 mt-md-0">
                {{-- Pastikan per_page tetap terbawa saat search --}}
                <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i
                            class="mdi mdi-magnify text-muted fs-4"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-0 bg-light fw-bold"
                        placeholder="Cari nama, No. RM, gejala, lokasi..." value="{{ request('search') }}">
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead class="bg-dark text-white">
                    <tr>
                        <th class="text-white text-center" style="width: 50px;">NO</th>
                        <th class="text-white">FOTO PASIEN</th>
                        <th class="text-white">IDENTITAS PASIEN</th>
                        <th class="text-white">RIWAYAT & LOKASI</th>
                        <th class="text-white">STATUS KONDISI</th>
                        <th class="text-white">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($equipments as $key => $eq)
                        <tr>
                            <td class="text-center fw-bold">{{ $equipments->firstItem() + $key }}</td>
                            <td>
                                @if($eq->hasMedia('equipments'))
                                    <div
                                        style="width: 80px; height: 80px; overflow: hidden; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                        <img src="{{ $eq->getFirstTemporaryUrl(now()->addHour(), 'equipments') }}" alt="Foto"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                @elseif($eq->gambar)
                                    <div
                                        style="width: 80px; height: 80px; overflow: hidden; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
                                        <img src="{{ asset('storage/' . $eq->gambar) }}" alt="Foto Lama"
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                @else
                                    <div class="bg-light text-muted d-flex align-items-center justify-content-center fw-bold"
                                        style="width: 80px; height: 80px; border-radius: 8px; border: 1px dashed #ccc;">
                                        <i class="mdi mdi-camera-off fs-3"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <h5 class="fw-bold text-primary mb-1" style="font-size: 1.15rem;">{{ $eq->merk }}</h5>
                                <div class="text-dark fw-bold mb-1" style="font-size: 0.95rem;"><i class="mdi mdi-medical-bag me-1"></i> Diagnosis/Gejala: <strong>{{ $eq->type }}</strong></div>
                                <div class="text-muted mb-1" style="font-size: 0.9rem;"><i class="mdi mdi-cake-variant text-danger me-1"></i> Tgl Lahir: <strong>{{ $eq->tanggal_lahir ? \Carbon\Carbon::parse($eq->tanggal_lahir)->translatedFormat('d M Y') : '-' }}</strong></div>
                                <div><span class="badge bg-light text-dark border px-2 py-1"><i class="mdi mdi-card-account-details-outline me-1"></i> No. RM: {{ $eq->serial_number }}</span></div>
                            </td>
                            <td>
                                <div class="mb-1 fw-bold text-dark"><i class="mdi mdi-map-marker text-danger fs-5 me-1"></i> {{ $eq->lokasi }}</div>
                                <div class="mb-1 text-muted" style="font-size: 0.92rem;"><i class="mdi mdi-calendar text-primary me-1"></i> Terdaftar: {{ \Carbon\Carbon::parse($eq->tanggal_pengadaan)->translatedFormat('d M Y') }}</div>
                                <div>
                                    <span class="badge bg-dark text-white shadow-sm">
                                        @if($eq->status_kepemilikan == 'Milik RS') BPJS Kesehatan @elseif($eq->status_kepemilikan == 'KSO') Asuransi Swasta @else Umum / Mandiri @endif
                                    </span>
                                </div>
                            </td>
                            <td>
                                @php
                                    $kondisiClass = [
                                        'Baik' => 'bg-success',
                                        'Rusak Ringan' => 'bg-warning text-dark border border-dark',
                                        'Rusak Berat' => 'bg-danger text-white'
                                    ];
                                    $kondisiLabel = [
                                        'Baik' => 'STABIL (RAWAT JALAN)',
                                        'Rusak Ringan' => 'GEJALA RINGAN',
                                        'Rusak Berat' => 'RAWAT INTENSIF'
                                    ];
                                @endphp
                                <div class="badge {{ $kondisiClass[$eq->kondisi] ?? 'bg-secondary' }} py-2 px-3 shadow-sm" style="font-size: 0.95rem;">
                                    {{ $kondisiLabel[$eq->kondisi] ?? strtoupper($eq->kondisi) }}
                                </div>
                            </td>
                            <td>
                                @if(auth()->user()->role !== 'viewer')
                                    <div class="d-flex gx-2">
                                        <button class="btn btn-outline-primary btn-sm px-3 py-2 me-2 shadow-sm"
                                            data-bs-toggle="modal" data-bs-target="#editEquipmentModal{{ $eq->id }}"
                                            title="Edit Data Pasien">
                                            <i class="mdi mdi-pencil fs-5"></i>
                                        </button>
                                        <button class="btn btn-outline-danger btn-sm px-3 py-2 shadow-sm" data-bs-toggle="modal"
                                            data-bs-target="#deleteEquipmentModal{{ $eq->id }}" title="Hapus Data Pasien">
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
                                <i class="mdi mdi-account-outline text-muted" style="font-size: 4rem;"></i>
                                <h4 class="mt-3 text-dark fw-bold">Belum Ada Pasien Terdaftar</h4>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 pt-3 border-top d-flex justify-content-center">
            {{ $equipments->appends(request()->input())->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
</div>
</div>

{{-- MODAL TAMBAH PASIEN --}}
<div class="modal fade" id="addEquipmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary px-4 py-3">
                <h5 class="modal-title fw-bold text-white fs-4"><i class="mdi mdi-account-plus me-2"></i>
                    Registrasi Pasien Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form action="{{ route('equipments.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body px-4 py-4 bg-light">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Nama Lengkap Pasien <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="merk" class="form-control form-control-lg bg-white" required
                                placeholder="Contoh: Budi Santoso">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Diagnosa Utama / Gejala <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="type" class="form-control form-control-lg bg-white" required
                                placeholder="Contoh: Demam Tinggi / Hipertensi">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">No. Rekam Medis (RM) <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="serial_number"
                                class="form-control form-control-lg bg-white border-primary" required
                                placeholder="Nomor Rekam Medis Pasien">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Tanggal Lahir Pasien <span
                                    class="text-danger">*</span></label>
                            <input type="date" name="tanggal_lahir" class="form-control form-control-lg bg-white" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Ruang Rawat / Lokasi <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="lokasi" class="form-control form-control-lg bg-white" required
                                placeholder="Contoh: Ruang Melati / Poliklinik">
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Kondisi Saat Ini <span
                                    class="text-danger">*</span></label>
                            <select name="kondisi" class="form-select form-select-lg bg-white fw-bold text-dark"
                                required>
                                <option value="Baik">Kondisi Stabil (Rawat Jalan)</option>
                                <option value="Rusak Ringan">Gejala Ringan</option>
                                <option value="Rusak Berat">Rawat Intensif</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Metode Pembayaran <span
                                    class="text-danger">*</span></label>
                            <select name="status_kepemilikan"
                                class="form-select form-select-lg bg-white fw-bold text-dark" required>
                                <option value="Milik RS">BPJS Kesehatan</option>
                                <option value="KSO">Asuransi Swasta</option>
                                <option value="Hibah">Umum / Mandiri</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Tanggal Registrasi <span
                                    class="text-danger">*</span></label>
                            <input type="date" name="tanggal_pengadaan" class="form-control form-control-lg bg-white"
                                required>
                        </div>
                        <div class="col-md-12 mb-4">
                            <label class="form-label text-dark fw-bold fs-5">Upload Foto / Identitas Pasien</label>
                            <input type="file" name="gambar" class="form-control form-control-lg bg-white"
                                accept="image/*">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-dark fw-bold fs-5">Catatan Riwayat Medis Lainnya (Opsional)</label>
                            <textarea name="spesifikasi" class="form-control bg-white" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white px-4 py-3">
                    <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4"><i class="mdi mdi-content-save me-1"></i>
                        SIMPAN KE DATABASE</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($equipments as $eq)
    {{-- MODAL EDIT --}}
    <div class="modal fade" id="editEquipmentModal{{ $eq->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark px-4 py-3">
                    <h5 class="modal-title fw-bold text-white fs-4"><i class="mdi mdi-pencil-box-outline me-2"></i> Perbarui Data Pasien</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="{{ route('equipments.update', $eq->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-body px-4 py-4 bg-light">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label text-dark fw-bold fs-5">Nama Lengkap Pasien</label>
                                <input type="text" name="merk" class="form-control form-control-lg" value="{{ $eq->merk }}"
                                    required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label text-dark fw-bold fs-5">Diagnosa Utama / Gejala</label>
                                <input type="text" name="type" class="form-control form-control-lg" value="{{ $eq->type }}"
                                    required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label text-dark fw-bold fs-5">No. Rekam Medis (RM)</label>
                                <input type="text" name="serial_number"
                                    class="form-control form-control-lg border-primary fw-bold"
                                    value="{{ $eq->serial_number }}" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label text-dark fw-bold fs-5">Tanggal Lahir Pasien</label>
                                <input type="date" name="tanggal_lahir" class="form-control form-control-lg"
                                    value="{{ $eq->tanggal_lahir }}" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label text-dark fw-bold fs-5">Ruang Rawat / Lokasi</label>
                                <input type="text" name="lokasi" class="form-control form-control-lg"
                                    value="{{ $eq->lokasi }}" required>
                            </div>
                            <div class="col-md-4 mb-4">
                                <label class="form-label text-dark fw-bold fs-5">Kondisi Klinis</label>
                                <select name="kondisi" class="form-select form-select-lg fw-bold text-dark" required>
                                    <option value="Baik" {{ $eq->kondisi == 'Baik' ? 'selected' : '' }}>Kondisi Stabil (Rawat Jalan)</option>
                                    <option value="Rusak Ringan" {{ $eq->kondisi == 'Rusak Ringan' ? 'selected' : '' }}>Gejala Ringan</option>
                                    <option value="Rusak Berat" {{ $eq->kondisi == 'Rusak Berat' ? 'selected' : '' }}>Rawat Intensif</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-4">
                                <label class="form-label text-dark fw-bold fs-5">Metode Pembayaran</label>
                                <select name="status_kepemilikan" class="form-select form-select-lg fw-bold text-dark"
                                    required>
                                    <option value="Milik RS" {{ $eq->status_kepemilikan == 'Milik RS' ? 'selected' : '' }}>BPJS Kesehatan</option>
                                    <option value="KSO" {{ $eq->status_kepemilikan == 'KSO' ? 'selected' : '' }}>Asuransi Swasta</option>
                                    <option value="Hibah" {{ $eq->status_kepemilikan == 'Hibah' ? 'selected' : '' }}>Umum / Mandiri</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-4">
                                <label class="form-label text-dark fw-bold fs-5">Tanggal Registrasi</label>
                                <input type="date" name="tanggal_pengadaan" class="form-control form-control-lg"
                                    value="{{ $eq->tanggal_pengadaan }}" required>
                            </div>
                            <div class="col-md-12 mb-4">
                                <label class="form-label text-dark fw-bold fs-5">Ganti Foto Pasien (Abaikan jika tetap)</label>
                                <input type="file" name="gambar" class="form-control form-control-lg" accept="image/*">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white px-4 py-3">
                        <button type="button" class="btn btn-light fw-bold px-4" data-bs-dismiss="modal">BATAL</button>
                        <button type="submit" class="btn btn-dark fw-bold px-4"><i class="mdi mdi-content-save me-1"></i>
                            SIMPAN PERUBAHAN</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL HAPUS --}}
    <div class="modal fade" id="deleteEquipmentModal{{ $eq->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger px-4 py-3">
                    <h5 class="modal-title fw-bold text-white fs-4"><i class="mdi mdi-alert-octagon me-2"></i> Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-5 bg-light">
                    <i class="mdi mdi-delete-alert text-danger mb-3" style="font-size: 5rem; display:block;"></i>
                    <h3 class="text-dark fw-bold">Hapus Data Pasien Ini?</h3>
                    <p class="text-dark mt-2" style="font-size: 1.15rem;">Segala riwayat medis <b>{{ $eq->merk }}</b> akan dihapus permanen.</p>
                </div>
                <div class="modal-footer bg-white px-4 py-3 d-flex justify-content-between">
                    <button type="button" class="btn btn-light fw-bold px-4 w-45" data-bs-dismiss="modal">BATALKAN</button>
                    <form action="{{ route('equipments.destroy', $eq->id) }}" method="POST" class="w-45">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger fw-bold w-100"><i
                                class="mdi mdi-delete-forever me-1"></i> YA, HAPUS</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach
@stop