@extends('layouts.staradmin')

@section('title', 'Dashboard')

@section('content_header')
<div class="row mb-4">
  <div class="col-sm-12">
    <div class="home-tab">
      <div class="d-sm-flex align-items-center justify-content-between border-bottom pb-3">
        <div>
          <h1 class="h2 text-dark font-weight-bold">Dashboard Pemantauan Pasien</h1>
          <p class="text-muted mb-0">Ringkasan riwayat berobat, status kondisi, dan rencana kontrol pasien rumah sakit.</p>
        </div>
        <div>
          <div class="btn-wrapper">
            <a href="{{ route('equipments.index') }}" class="btn btn-primary text-white me-0 px-4 py-2 fw-bold"><i class="mdi mdi-plus-circle me-1"></i> Pasien Baru</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@stop

@section('content')
<div class="row">
    <!-- Total Pasien -->
    <div class="col-lg-4 col-md-6 col-sm-12 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h4 class="card-title card-title-dash mb-0 text-uppercase fw-bold text-primary">Total Pasien</h4>
                    <div class="badge badge-opacity-primary"><i class="mdi mdi-account-group fs-4 text-primary"></i></div>
                </div>
                <h2 class="text-dark fw-bold mb-0" style="font-size: 2.2rem;">{{ number_format($totalAlat) }}</h2>
                <div class="mt-3">
                    <a href="{{ route('equipments.index') }}" class="text-decoration-none text-primary fw-bold" style="font-size: 0.95rem;">Lihat Detail <i class="mdi mdi-arrow-right-circle"></i></a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stabil EWS (Green) -->
    <div class="col-lg-2 col-md-3 col-sm-6 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h4 class="card-title card-title-dash mb-0 text-uppercase fw-bold text-success">Stabil EWS</h4>
                    <div class="badge badge-opacity-success bg-success"><i class="mdi mdi-emoticon-happy fs-4 text-white"></i></div>
                </div>
                <h2 class="text-dark fw-bold mb-0" style="font-size: 2.2rem;">{{ number_format($alatBaik) }}</h2>
                <div class="mt-3">
                    <a href="{{ route('equipments.index') }}" class="text-decoration-none text-success fw-bold" style="font-size: 0.95rem;">Lihat Detail <i class="mdi mdi-arrow-right-circle"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Observasi EWS (Yellow) -->
    <div class="col-lg-2 col-md-3 col-sm-6 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h4 class="card-title card-title-dash mb-0 text-uppercase fw-bold text-warning">Observasi EWS</h4>
                    <div class="badge badge-opacity-warning bg-warning"><i class="mdi mdi-alert-circle fs-4 text-dark"></i></div>
                </div>
                <h2 class="text-dark fw-bold mb-0" style="font-size: 2.2rem;">{{ number_format($alatRusakRingan) }}</h2>
                <div class="mt-3">
                    <a href="{{ route('equipments.index') }}" class="text-decoration-none text-warning fw-bold text-dark" style="font-size: 0.95rem;">Lihat Detail <i class="mdi mdi-arrow-right-circle"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Pemantauan Ketat (Orange) -->
    <div class="col-lg-2 col-md-3 col-sm-6 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h4 class="card-title card-title-dash mb-0 text-uppercase fw-bold" style="color: #fd7e14 !important;">Ketat EWS</h4>
                    <div class="badge text-white" style="background-color: #fd7e14;"><i class="mdi mdi-alert fs-4"></i></div>
                </div>
                <h2 class="text-dark fw-bold mb-0" style="font-size: 2.2rem;">{{ number_format($alatOrange) }}</h2>
                <div class="mt-3">
                    <a href="{{ route('equipments.index') }}" class="text-decoration-none fw-bold" style="font-size: 0.95rem; color: #fd7e14 !important;">Lihat Detail <i class="mdi mdi-arrow-right-circle"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Intensif EWS (Red) -->
    <div class="col-lg-2 col-md-3 col-sm-6 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h4 class="card-title card-title-dash mb-0 text-uppercase fw-bold text-danger">Intensif EWS</h4>
                    <div class="badge badge-opacity-danger bg-danger"><i class="mdi mdi-pulse fs-4 text-white"></i></div>
                </div>
                <h2 class="text-dark fw-bold mb-0" style="font-size: 2.2rem;">{{ number_format($alatRusakBerat) }}</h2>
                <div class="mt-3">
                    <a href="{{ route('equipments.index') }}" class="text-decoration-none text-danger fw-bold" style="font-size: 0.95rem;">Lihat Detail <i class="mdi mdi-arrow-right-circle"></i></a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Peringatan Kalibrasi -->
    <div class="col-lg-8 grid-margin stretch-card">
        <div class="card shadow-sm border-0" style="border-top: 4px solid #F59F00 !important;">
            <div class="card-body px-4 py-4">
                <h4 class="card-title text-warning fw-bold" style="font-size: 1.35rem;">
                    <i class="mdi mdi-bell-alert fs-3 align-middle me-2"></i> PERINGATAN KONTROL PASIEN (1 BULAN KEDEPAN)
                </h4>
                <p class="card-description">Daftar pasien dengan tenggat janji kontrol rutin atau konsultasi dokter terdekat.</p>
                
                <div class="table-responsive bg-white rounded">
                    <table class="table table-striped table-hover table-bordered">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th class="text-white fw-bold">Nama Pasien</th>
                                <th class="text-white fw-bold">No. RM (Rekam Medis)</th>
                                <th class="text-white fw-bold">Tanggal Kontrol Kembali</th>
                                <th class="text-white fw-bold text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($nearCalibrationAlat as $cal)
                                @php
                                    $tgl = \Carbon\Carbon::parse($cal->tanggal_kalibrasi_berikutnya);
                                    $isOverdue = $tgl->isPast();
                                @endphp
                                <tr>
                                    <td class="fw-bold" style="font-size: 1.05rem;">{{ $cal->equipment->merk }} - {{ $cal->equipment->type }}</td>
                                    <td><strong class="text-primary">{{ $cal->equipment->serial_number }}</strong></td>
                                    <td class="fw-bold {{ $isOverdue ? 'text-danger' : 'text-warning' }}">
                                        {{ $tgl->translatedFormat('d M Y') }}
                                    </td>
                                    <td class="text-center">
                                        @if($isOverdue)
                                            <span class="badge bg-danger text-white py-2 px-3">TERLEWAT</span>
                                        @else
                                            <span class="badge bg-warning text-dark py-2 px-3 fw-bold border border-dark">MENDEKATI</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 fs-5 text-muted fw-bold">Aman: Belum ada jadwal kontrol mendesak terdekat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Jaminan/Pembayaran -->
    <div class="col-lg-4 grid-margin stretch-card">
        <div class="card shadow-sm border-0" style="border-top: 4px solid #1F3BB3 !important;">
            <div class="card-body">
                <h4 class="card-title text-primary fw-bold" style="font-size: 1.35rem;">
                    <i class="mdi mdi-credit-card fs-3 align-middle me-2"></i> METODE PEMBAYARAN
                </h4>
                <p class="card-description">Rincian penjamin/pembayaran berobat pasien.</p>
                
                <div class="mt-4">
                  <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                      <div class="d-flex align-items-center">
                          <i class="mdi mdi-account-card-details fs-2 text-primary me-3"></i>
                          <h6 class="mb-0 fw-bold fs-5">BPJS Kesehatan</h6>
                      </div>
                      <h4 class="mb-0 fw-bold text-primary">{{ number_format($milikRS) }}</h4>
                  </div>
                  <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                      <div class="d-flex align-items-center">
                          <i class="mdi mdi-shield-check fs-2 text-success me-3"></i>
                          <h6 class="mb-0 fw-bold fs-5">Asuransi Swasta</h6>
                      </div>
                      <h4 class="mb-0 fw-bold text-success">{{ number_format($kso) }}</h4>
                  </div>
                  <div class="d-flex justify-content-between align-items-center mb-2">
                      <div class="d-flex align-items-center">
                          <i class="mdi mdi-cash fs-2 text-warning me-3"></i>
                          <h6 class="mb-0 fw-bold fs-5">Umum / Mandiri</h6>
                      </div>
                      <h4 class="mb-0 fw-bold text-warning">{{ number_format($hibah) }}</h4>
                  </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Peringatan Pemeliharaan -->
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card shadow-sm border-0" style="border-top: 4px solid #DC3545 !important;">
            <div class="card-body px-4 py-4">
                <h4 class="card-title text-danger fw-bold" style="font-size: 1.35rem;">
                    <i class="mdi mdi-alert-octagon fs-3 align-middle me-2"></i> JADWAL KUNJUNGAN PASIEN MENDESAK (1 BULAN KEDEPAN)
                </h4>
                <p class="card-description">Daftar pasien yang terdaftar untuk melakukan kunjungan/kontrol tindak lanjut segera bulan ini.</p>
                
                <div class="table-responsive bg-white rounded">
                    <table class="table table-striped table-hover table-bordered">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th class="text-white fw-bold">Nama Pasien</th>
                                <th class="text-white fw-bold">No. RM (Rekam Medis)</th>
                                <th class="text-white fw-bold">Jenis Tindakan</th>
                                <th class="text-white fw-bold">Tanggal Rencana Kontrol</th>
                                <th class="text-white fw-bold text-center">Status Tunggu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($nearMaintenanceAlat as $mnt)
                                @php
                                    $mtgl = \Carbon\Carbon::parse($mnt->tanggal_jadwal_berikutnya);
                                    $misOverdue = $mtgl->isPast();
                                @endphp
                                <tr>
                                    <td class="fw-bold" style="font-size: 1.05rem;">{{ $mnt->equipment->merk }} - {{ $mnt->equipment->type }}</td>
                                    <td><strong class="text-primary">{{ $mnt->equipment->serial_number }}</strong></td>
                                    <td>
                                        @if($mnt->jenis_pemeliharaan == 'Preventif')
                                            <span class="badge bg-success px-3 py-2 text-white shadow-sm">Rutin / Kontrol</span>
                                        @else
                                            <span class="badge bg-warning px-3 py-2 text-dark shadow-sm">Darurat / UGD</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold {{ $misOverdue ? 'text-danger' : 'text-danger' }}">
                                        {{ $mtgl->translatedFormat('d M Y') }}
                                    </td>
                                    <td class="text-center">
                                        @if($misOverdue)
                                            <span class="badge bg-danger text-white py-2 px-3 fw-bold">TENGGAT TERLEWAT</span>
                                        @else
                                            <span class="badge bg-warning text-dark border border-dark py-2 px-3 fw-bold"><i class="mdi mdi-clock-outline me-1"></i> SEGERA ({{ (int) ceil(\Carbon\Carbon::now()->startOfDay()->diffInDays($mtgl->startOfDay(), false)) }} HARI KEDEPAN)</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 fs-5 text-muted fw-bold">Aman: Tidak ada antrean kontrol darurat.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
