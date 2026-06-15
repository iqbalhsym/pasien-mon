<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pelayanan Pasien - {{ $equipment->merk }}</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- MDI Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">
    <style>
        body {
            background-color: #f4f7f9;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #333;
        }
        .public-header {
            background: linear-gradient(135deg, #1F3BB3 0%, #0d6efd 100%);
            color: white;
            padding: 2.5rem 1rem;
            text-align: center;
            border-bottom-left-radius: 40px;
            border-bottom-right-radius: 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .main-container {
            margin-top: -30px;
            padding-bottom: 50px;
        }
        .equipment-card {
            background: white;
            border-radius: 20px;
            border: none;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 25px;
        }
        .history-card {
            background: white;
            border-radius: 20px;
            border: none;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .badge-sn {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 30px;
            font-size: 0.9rem;
        }
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        .history-item {
            border-left: 4px solid #0d6efd;
            margin-bottom: 15px;
            padding: 15px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.03);
        }
        .history-item.Preventif { border-left-color: #198754; }
        .history-item.Korektif { border-left-color: #ffc107; }

        .btn-login-bridge {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #212529;
            color: white;
            border-radius: 50px;
            padding: 12px 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            font-weight: 600;
            z-index: 1000;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn-login-bridge:hover {
            background: #000;
            color: #fff;
            transform: translateY(-3px);
        }
        .logo-rs {
            width: 40px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <div class="public-header">
        <img src="{{ asset('images/favicon-icon.png') }}" alt="Logo" class="logo-rs">
        <h1 class="h3 fw-bold mb-2">Riwayat Pelayanan Pasien</h1>
        <div class="d-flex justify-content-center">
            <span class="badge-sn"><i class="mdi mdi-card-account-details-outline me-1"></i> No. RM: {{ $equipment->serial_number }}</span>
        </div>
    </div>

    <div class="container main-container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Equipment Info Card -->
                <div class="equipment-card p-4">
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <h2 class="h4 fw-bold text-primary mb-1">{{ $equipment->merk }}</h2>
                            <p class="text-muted mb-3">{{ $equipment->type }}</p>
                             <div class="d-flex flex-wrap gap-2">
                                <span class="badge bg-light text-dark border"><i class="mdi mdi-map-marker text-danger me-1"></i> {{ $equipment->lokasi ?: '-' }}</span>
                                @if($equipment->lantai)
                                    <span class="badge bg-light text-dark border"><i class="mdi mdi-layers-outline text-info me-1"></i> Lantai: {{ $equipment->lantai }}</span>
                                @endif
                                @php
                                    $eqKondisiClass = [
                                        'Baik' => 'badge bg-success-subtle text-success fw-bold border',
                                        'Stabil EWS' => 'badge bg-success-subtle text-success fw-bold border',
                                        'Rusak Ringan' => 'badge bg-warning-subtle text-warning-emphasis fw-bold border',
                                        'Stabil perlu observasi rutin EWS' => 'badge bg-warning-subtle text-warning-emphasis fw-bold border',
                                        'Perlu pemantauan khusus EWS' => 'badge bg-warning-subtle text-warning-emphasis fw-bold border',
                                        'Perlu pemantauan ketat EWS' => 'badge text-white fw-bold border',
                                        'Rusak Berat' => 'badge bg-danger-subtle text-danger fw-bold border',
                                        'Intensif ESW' => 'badge bg-danger-subtle text-danger fw-bold border',
                                        'Intensif EWS' => 'badge bg-danger-subtle text-danger fw-bold border',
                                    ];
                                    $eqKondisiStyle = [
                                        'Perlu pemantauan ketat EWS' => 'background-color: #fd7e14;',
                                    ];
                                    $eqKondisiLabel = [
                                        'Baik' => 'STABIL',
                                        'Stabil EWS' => 'STABIL EWS',
                                        'Rusak Ringan' => 'GEJALA RINGAN',
                                        'Stabil perlu observasi rutin EWS' => 'STABIL PERLU OBSERVASI RUTIN EWS',
                                        'Perlu pemantauan khusus EWS' => 'PERLU PEMANTAUAN KHUSUS EWS',
                                        'Perlu pemantauan ketat EWS' => 'PERLU PEMANTAUAN KETAT EWS',
                                        'Rusak Berat' => 'RAWAT INTENSIF',
                                        'Intensif ESW' => 'INTENSIF EWS',
                                        'Intensif EWS' => 'INTENSIF EWS',
                                    ];
                                @endphp
                                <span class="{{ $eqKondisiClass[$equipment->kondisi] ?? 'badge bg-light text-dark border' }}" style="{{ $eqKondisiStyle[$equipment->kondisi] ?? '' }}">
                                    <i class="mdi mdi-check-circle text-success me-1"></i> Kondisi: {{ $eqKondisiLabel[$equipment->kondisi] ?? ($equipment->kondisi ?: 'Baik') }}
                                </span>
                            </div>
                        </div>
                        <div class="col-md-5 text-md-end mt-3 mt-md-0">
                             <div class="text-muted small">Total Pelayanan Medis:</div>
                             <div class="h2 fw-bold text-dark">{{ $maintenances->count() }}</div>
                        </div>
                    </div>
                </div>

                <!-- History Section -->
                <h5 class="fw-bold mb-3 px-2"><i class="mdi mdi-clock-outline me-1"></i> Catatan Historis Pelayanan</h5>
                
                @forelse($maintenances as $mnt)
                <div class="history-item {{ $mnt->jenis_pemeliharaan }}">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            @if($mnt->jenis_pemeliharaan == 'Preventif')
                                <span class="badge bg-success-subtle text-success px-2 py-1 small">Rutin / Kontrol</span>
                            @elseif($mnt->jenis_pemeliharaan == 'Pemindahan Poli')
                                <span class="badge bg-info-subtle text-info-emphasis px-2 py-1 small"><i class="mdi mdi-swap-horizontal me-1"></i> Rujukan / Pindah Poli</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis px-2 py-1 small">Darurat / UGD</span>
                            @endif
                            <div class="fw-bold mt-1 text-dark">{{ \Carbon\Carbon::parse($mnt->tanggal_pelaksanaan)->translatedFormat('d F Y') }}</div>
                        </div>
                        <div class="text-end">
                            <div class="small text-muted">Dokter / Tenaga Medis:</div>
                            <div class="small fw-bold text-dark">{{ $mnt->petugas }}</div>
                        </div>
                    </div>
                    @php
                        $borderClass = 'border-secondary';
                        if ($mnt->jenis_pemeliharaan == 'Preventif') {
                            $borderClass = 'border-success';
                        } elseif ($mnt->jenis_pemeliharaan == 'Pemindahan Poli') {
                            $borderClass = 'border-info';
                        }
                    @endphp
                    <div class="bg-light p-3 rounded border-start border-3 {{ $borderClass }}" style="font-size: 0.95rem; font-style: italic;">
                        "{{ $mnt->tindakan_hasil }}"
                    </div>

                    <!-- Metadata Clinis for Visit -->
                    <div class="mt-3 p-3 bg-white rounded border border-light shadow-sm">
                        <div class="row g-3">
                            <div class="col-6 col-md-3 border-end">
                                <div class="small text-muted mb-1"><i class="mdi mdi-clipboard-pulse text-primary me-1"></i> Diagnosa / Gejala</div>
                                <div class="fw-bold text-dark" style="font-size: 0.95rem;">{{ $mnt->diagnosa_gejala ?: '-' }}</div>
                            </div>
                            <div class="col-6 col-md-3 border-end">
                                <div class="small text-muted mb-1"><i class="mdi mdi-map-marker text-danger me-1"></i> Ruang Rawat / Lokasi</div>
                                <div class="fw-bold text-dark" style="font-size: 0.95rem;">{{ $mnt->lokasi_rawat ?: '-' }}</div>
                            </div>
                            <div class="col-6 col-md-3 border-end">
                                <div class="small text-muted mb-1"><i class="mdi mdi-heart-pulse text-success me-1"></i> Kondisi Klinis</div>
                                <div>
                                    @if($mnt->kondisi_klinis == 'Baik' || $mnt->kondisi_klinis == 'Stabil EWS')
                                        <span class="badge bg-success-subtle text-success fw-bold">STABIL EWS</span>
                                    @elseif($mnt->kondisi_klinis == 'Rusak Ringan' || $mnt->kondisi_klinis == 'Stabil perlu observasi rutin EWS' || $mnt->kondisi_klinis == 'Perlu pemantauan khusus EWS')
                                        <span class="badge bg-warning-subtle text-warning-emphasis fw-bold">OBSERVASI EWS</span>
                                    @elseif($mnt->kondisi_klinis == 'Perlu pemantauan ketat EWS')
                                        <span class="badge fw-bold text-white" style="background-color: #fd7e14;">PEMANTAUAN KETAT EWS</span>
                                    @elseif($mnt->kondisi_klinis == 'Rusak Berat' || $mnt->kondisi_klinis == 'Intensif ESW' || $mnt->kondisi_klinis == 'Intensif EWS')
                                        <span class="badge bg-danger-subtle text-danger fw-bold">INTENSIF EWS</span>
                                    @else
                                        <span class="text-dark fw-bold">{{ $mnt->kondisi_klinis ?: '-' }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="small text-muted mb-1"><i class="mdi mdi-wallet text-info me-1"></i> Metode Pembayaran</div>
                                <div>
                                    @if($mnt->metode_pembayaran == 'Milik RS')
                                        <span class="badge bg-primary-subtle text-primary fw-bold">BPJS Kesehatan</span>
                                    @elseif($mnt->metode_pembayaran == 'KSO')
                                        <span class="badge bg-info-subtle text-info-emphasis fw-bold">Asuransi Swasta</span>
                                    @elseif($mnt->metode_pembayaran == 'Hibah')
                                        <span class="badge bg-secondary-subtle text-secondary-emphasis fw-bold">Umum / Mandiri</span>
                                    @else
                                        <span class="text-dark fw-bold">-</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="mdi mdi-clipboard-text-off-outline text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-2">Belum ada catatan riwayat pelayanan medis untuk pasien ini.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Login Bridge -->
    <a href="{{ route('maintenances.history', $equipment->serial_number) }}" class="btn-login-bridge">
        <i class="mdi mdi-login-variant me-1"></i> Login Staf Medis &raquo;
    </a>

    <footer class="text-center text-muted py-4 small">
        &copy; 2026 | RS Universitas Indonesia
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
