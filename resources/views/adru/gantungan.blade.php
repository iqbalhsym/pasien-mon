@extends('layouts.staradmin')
@section('title', 'Transaksi Gantungan')

@section('content_header')
<div class="d-sm-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
  <div>
    <h1 class="h2 text-dark font-weight-bold">Transaksi Gantungan</h1>
    <p class="text-muted mb-0">Daftar alat kesehatan dan tindakan yang belum masuk billing.</p>
  </div>
</div>
@stop

@section('content')
@php
// Group outstanding items by patient
$gantunganByPatient = [];
foreach($alkesList as $item) {
    $rm = $item->admission->rm ?? 'UNKNOWN';
    if(!isset($gantunganByPatient[$rm])) {
        $gantunganByPatient[$rm] = [
            'rm' => $rm,
            'name' => $item->admission->name ?? 'Pasien',
            'room' => $item->admission->room ?? '-',
            'alkes' => [],
            'tindakan' => [],
        ];
    }
    $gantunganByPatient[$rm]['alkes'][] = $item;
}
foreach($tindakanList as $item) {
    $rm = $item->admission->rm ?? 'UNKNOWN';
    if(!isset($gantunganByPatient[$rm])) {
        $gantunganByPatient[$rm] = [
            'rm' => $rm,
            'name' => $item->admission->name ?? 'Pasien',
            'room' => $item->admission->room ?? '-',
            'alkes' => [],
            'tindakan' => [],
        ];
    }
    $gantunganByPatient[$rm]['tindakan'][] = $item;
}
$gantunganByPatient = array_values($gantunganByPatient);
@endphp

{{-- Flash success message --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="mdi mdi-check-circle me-2"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- PASIEN WITH GANTUNGAN TABLE --}}
<div class="card card-rounded shadow-sm mb-4">
    <div class="card-header">
        <h6 class="mb-0 fw-bold">Daftar Pasien dengan Transaksi Gantung ({{ count($gantunganByPatient) }})</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:500px;overflow-y:auto;">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light sticky-top">
                    <tr style="font-size:0.72rem;" class="text-uppercase text-muted">
                        <th class="px-3">RM</th>
                        <th class="px-3">Nama</th>
                        <th class="px-3">Ruang</th>
                        <th class="text-center px-3">Gantungan Alkes</th>
                        <th class="text-center px-3">Gantungan Tindakan</th>
                        <th class="text-end px-3">Aksi</th>
                    </tr>
                </thead>
                <tbody style="font-size:0.78rem;">
                    @forelse($gantunganByPatient as $p)
                    <tr style="cursor:pointer;" onclick="showGantunganDetail('{{ $p['rm'] }}')">
                        <td class="px-3 fw-bold text-primary">{{ $p['rm'] }}</td>
                        <td class="px-3 fw-semibold">{{ $p['name'] }}</td>
                        <td class="px-3">{{ $p['room'] }}</td>
                        <td class="text-center px-3">
                            @if(count($p['alkes']) > 0)
                            <span class="badge bg-danger">{{ count($p['alkes']) }} item</span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center px-3">
                            @if(count($p['tindakan']) > 0)
                            <span class="badge bg-warning text-dark">{{ count($p['tindakan']) }} item</span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end px-3">
                            <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); showGantunganDetail('{{ $p['rm'] }}')">
                                Lihat Detail
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Tidak ada transaksi gantung.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- REVENUE LEAKAGE SECTION --}}
<div class="card card-rounded shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">Potensi Revenue Leakage (Belum Masuk Billing)</h6>
        <span class="text-danger fw-bold fw-mono">Total: Rp 11.450.000</span>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-bold text-uppercase mb-3" style="font-size:0.72rem;">Berdasarkan Unit Penunjang</h6>
                @php
                $leakageData = [
                    ['name' => 'Farmasi (Resep & Obat Pulang)', 'count' => 12, 'sum' => 4200000, 'badge' => 'bg-danger'],
                    ['name' => 'Laboratorium (Hasil PA/Susulan)', 'count' => 8, 'sum' => 1250000, 'badge' => 'bg-warning text-dark'],
                    ['name' => 'Radiologi (Rontgen/CT Scan)', 'count' => 4, 'sum' => 850000, 'badge' => 'bg-primary'],
                    ['name' => 'COT / Kamar Operasi', 'count' => 3, 'sum' => 1100000, 'badge' => 'bg-purple'],
                    ['name' => 'ICU (Keterangan Kendala)', 'count' => 2, 'sum' => 950000, 'badge' => 'bg-info'],
                    ['name' => 'Alat Kesehatan (Ventilator/Stapler)', 'count' => 5, 'sum' => 3100000, 'badge' => 'bg-secondary'],
                ];
                @endphp
                @foreach($leakageData as $row)
                <div class="d-flex justify-content-between align-items-center p-2 bg-light border rounded mb-2" style="font-size:0.78rem;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge {{ $row['badge'] }}" style="width:10px;height:10px;padding:0;border-radius:50%;"></span>
                        <span class="fw-semibold">{{ $row['name'] }}</span>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold">Rp {{ number_format($row['sum'], 0, ',', '.') }}</div>
                        <div class="text-muted" style="font-size:0.65rem;">{{ $row['count'] }} Transaksi</div>
                    </div>
                </div>
                @endforeach
            </div>
            <div class="col-md-6">
                <div class="bg-light border rounded p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <h6 class="fw-bold text-muted text-uppercase mb-2" style="font-size:0.72rem;">Penjelasan Revenue Leakage</h6>
                        <p style="font-size:0.78rem;" class="text-muted">
                            Revenue leakage (kebocoran pendapatan) terjadi apabila pasien dideklarasikan pulang oleh DPJP, namun data administrasi alkes, resep obat, patologi anatomi, atau pagu jasa dokter (jasa operator & anestesi) belum diselesaikan/diinput ke sistem billing utama (SIMRS). Hal ini mengakibatkan keterlambatan penagihan berkas klaim BPJS atau tagihan umum ke pasien.
                        </p>
                    </div>
                    <div class="border-top pt-3 mt-3">
                        <p class="text-muted text-uppercase fw-bold mb-1" style="font-size:0.65rem;">Tindakan Pencegahan</p>
                        <p style="font-size:0.78rem;" class="text-muted mb-0">Gunakan menu <a href="{{ route('adru.manual') }}" class="fw-bold">Entri Data Manual</a> untuk langsung melengkapi billing tertunda.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Gantungan Detail Modal --}}
<div class="modal fade" id="gantunganDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="gantunganModalTitle">Detail Transaksi Gantungan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="gantunganModalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@php
$gantunganJson = collect($gantunganByPatient)->map(function($p) {
    return [
        'rm' => $p['rm'],
        'name' => $p['name'],
        'room' => $p['room'],
        'alkes' => collect($p['alkes'])->map(fn($i) => ['id' => $i->id, 'item_name' => $i->item_name, 'price' => $i->price])->toArray(),
        'tindakan' => collect($p['tindakan'])->map(fn($i) => ['id' => $i->id, 'item_name' => $i->item_name, 'tgl_tindakan' => $i->tgl_tindakan])->toArray(),
    ];
})->toJson();
@endphp
@stop

@section('js')
<script>
const gantunganData = @json(json_decode($gantunganJson));

function showGantunganDetail(rm) {
    const patient = gantunganData.find(p => p.rm === rm);
    if (!patient) return;

    document.getElementById('gantunganModalTitle').textContent =
        'Detail Gantungan: ' + patient.name + ' (' + patient.rm + ') — ' + patient.room;

    const alkesHtml = patient.alkes.length > 0 ? `
        <h6 class="fw-bold mb-2" style="font-size:0.78rem;">Gantungan Alat Kesehatan (${patient.alkes.length} item)</h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm table-hover">
                <thead class="table-light"><tr class="text-muted" style="font-size:0.72rem;"><th>Item</th><th class="text-end">Harga</th><th class="text-center">Aksi</th></tr></thead>
                <tbody style="font-size:0.78rem;">
                    ${patient.alkes.map(item => `
                        <tr>
                            <td>${item.item_name}</td>
                            <td class="text-end fw-bold">Rp ${parseInt(item.price || 0).toLocaleString('id-ID')}</td>
                            <td class="text-center">
                                <form action="/adru/outstanding/${item.id}/resolve" method="POST">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <button class="btn btn-success btn-sm text-white py-0" style="font-size:0.7rem;">Selesai</button>
                                </form>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    ` : '';

    const tindakanHtml = patient.tindakan.length > 0 ? `
        <h6 class="fw-bold mb-2" style="font-size:0.78rem;">Gantungan Tindakan (${patient.tindakan.length} item)</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead class="table-light"><tr class="text-muted" style="font-size:0.72rem;"><th>Tindakan</th><th class="text-center">Tgl Tindakan</th><th class="text-center">Aksi</th></tr></thead>
                <tbody style="font-size:0.78rem;">
                    ${patient.tindakan.map(item => `
                        <tr>
                            <td>${item.item_name}</td>
                            <td class="text-center">${item.tgl_tindakan ? item.tgl_tindakan.substring(0,10) : '-'}</td>
                            <td class="text-center">
                                <form action="/adru/outstanding/${item.id}/resolve" method="POST">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <button class="btn btn-success btn-sm text-white py-0" style="font-size:0.7rem;">Selesai</button>
                                </form>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    ` : '';

    document.getElementById('gantunganModalBody').innerHTML = alkesHtml + tindakanHtml ||
        '<p class="text-muted text-center py-3">Tidak ada item gantungan.</p>';

    new bootstrap.Modal(document.getElementById('gantunganDetailModal')).show();
}
</script>
@stop
