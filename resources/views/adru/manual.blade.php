@extends('layouts.staradmin')
@section('title', 'Entri Data Manual')

@section('content_header')
<div class="d-sm-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
  <div>
    <h1 class="h2 text-dark font-weight-bold">Entri Data Manual</h1>
    <p class="text-muted mb-0">Silakan pilih jenis entri data sesuai dengan sumber spreadsheet.</p>
  </div>
</div>
@stop

@section('content')
{{-- Flash success message --}}
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="mdi mdi-check-circle me-2"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="mdi mdi-alert-circle me-2"></i>
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- TYPE SELECTOR TAB --}}
<div class="card card-rounded shadow-sm mb-4">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-2">
            @foreach([
                ['id' => 'ranap', 'label' => 'Active Inpatient (RANAP)', 'sub' => 'DASHADRU RANAP'],
                ['id' => 'icu', 'label' => 'Discharge ICU', 'sub' => 'DISCHARGE PASIEN ICU NICU'],
                ['id' => 'cot', 'label' => 'Tindakan COT', 'sub' => 'BILLING ADM COT'],
                ['id' => 'outstanding', 'label' => 'Outstanding Item', 'sub' => 'Alkes & Tindakan'],
            ] as $type)
            <button 
                class="btn btn-sm input-type-btn {{ old('_type', 'ranap') === $type['id'] ? 'btn-primary' : 'btn-outline-secondary' }}"
                data-type="{{ $type['id'] }}"
                type="button">
                <div>{{ $type['label'] }}</div>
                <div style="font-size:0.65rem;opacity:0.75;">{{ $type['sub'] }}</div>
            </button>
            @endforeach
        </div>
    </div>
</div>

{{-- RANAP FORM --}}
<div class="card card-rounded shadow-sm input-form-section" id="form-ranap">
    <div class="card-body">
        <h6 class="fw-bold mb-4">Form Entri Data Ranap Reguler</h6>
        <form action="{{ route('adru.patients.store-ranap') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">No. RM <span class="text-primary" style="font-size:0.7rem;">(Otomatis)</span></label>
                    <input type="text" name="rm" class="form-control form-control-sm" placeholder="e.g. 00-12-34-56" value="{{ old('rm') }}" id="rm_ranap">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Nama Pasien <span id="dob_ranap_info" class="text-muted fw-normal ms-2" style="font-size:0.7rem;"></span></label>
                    <input type="text" name="name" class="form-control form-control-sm" required value="{{ old('name') }}" placeholder="e.g. Maria Ulfa" id="name_ranap">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Cara Bayar / Jaminan</label>
                    <input type="text" name="insurance" class="form-control form-control-sm" required value="{{ old('insurance', 'BPJS Kesehatan') }}" placeholder="e.g. BPJS Kesehatan / UMUM" id="insurance_ranap">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Lantai / Floor</label>
                    <input type="text" name="floor" class="form-control form-control-sm" required value="{{ old('floor', 'Lantai 5') }}" placeholder="Lantai 5">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Ruang</label>
                    <input type="text" name="room" class="form-control form-control-sm" required value="{{ old('room') }}" placeholder="e.g. Kelas 1 A">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Dokter DPJP</label>
                    <input type="text" name="dpjp" class="form-control form-control-sm" required value="{{ old('dpjp', 'dr. Andi S, Sp.PD') }}" placeholder="e.g. dr. Andi S, Sp.PD">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Rencana Tanggal Pulang</label>
                    <input type="date" name="tgl_rencana_pulang" class="form-control form-control-sm" required value="{{ old('tgl_rencana_pulang') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Jam Rencana Pulang</label>
                    <input type="time" name="jam_rencana_pulang" class="form-control form-control-sm" value="{{ old('jam_rencana_pulang', '08:00') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Total Billing (Rp)</label>
                    <input type="number" name="total_bill" class="form-control form-control-sm" value="{{ old('total_bill', '5000000') }}" placeholder="e.g. 5000000">
                </div>
            </div>

            {{-- Checklist Status --}}
            <div class="border rounded p-3 bg-light mt-4">
                <h6 class="fw-bold mb-3" style="font-size:0.78rem;">Status Checklist Kepulangan</h6>
                <div class="row g-2">
                    @foreach(['lab_status' => 'Laboratorium', 'bedside_status' => 'Bedside', 'admin_ok_status' => 'Admin OK (Pagu)', 'farmasi_status' => 'Farmasi', 'radiologi_status' => 'Radiologi'] as $key => $label)
                    <div class="col-md-4 col-6">
                        <label class="form-label fw-bold" style="font-size:0.7rem;">{{ $label }}</label>
                        <select name="{{ $key }}" class="form-select form-select-sm">
                            <option value="none" {{ old($key, 'none') == 'none' ? 'selected' : '' }}>Tidak Ada</option>
                            <option value="success" {{ old($key) == 'success' ? 'selected' : '' }}>Selesai (OK)</option>
                            <option value="pending" {{ old($key) == 'pending' ? 'selected' : '' }}>Proses (Pending)</option>
                            <option value="error" {{ old($key) == 'error' ? 'selected' : '' }}>Kendala (Error)</option>
                        </select>
                    </div>
                    @endforeach
                    <div class="col-md-4 col-6">
                        <label class="form-label fw-bold" style="font-size:0.7rem;">Tindakan COT</label>
                        <select name="cot_status" class="form-select form-select-sm">
                            <option value="TIDAK">TIDAK</option>
                            <option value="YA">YA</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label fw-bold" style="font-size:0.78rem;">Keterangan Proses / Notes</label>
                <textarea name="keterangan_proses" class="form-control form-control-sm" rows="3" placeholder="Catatan proses rujukan, resep, atau kendala...">{{ old('keterangan_proses') }}</textarea>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4 border-top pt-3">
                <button type="submit" name="action" value="simpan" class="btn btn-secondary btn-sm fw-bold">Simpan Data</button>
                <button type="submit" name="action" value="selesai" class="btn btn-success btn-sm fw-bold text-white">Selesai & Discharge</button>
            </div>
        </form>
    </div>
</div>

{{-- ICU FORM --}}
<div class="card card-rounded shadow-sm input-form-section d-none" id="form-icu">
    <div class="card-body">
        <h6 class="fw-bold mb-4">Form Entri Data Discharge ICU</h6>
        <form action="{{ route('adru.patients.store-icu') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">No. RM <span class="text-primary" style="font-size:0.7rem;">(Otomatis)</span></label>
                    <input type="text" name="rm" class="form-control form-control-sm" placeholder="e.g. 00358932" required id="rm_icu">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Nama Pasien <span id="dob_icu_info" class="text-muted fw-normal ms-2" style="font-size:0.7rem;"></span></label>
                    <input type="text" name="name" class="form-control form-control-sm" required placeholder="e.g. Yaman. TN" id="name_icu">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Jaminan / Jasa</label>
                    <input type="text" name="insurance" class="form-control form-control-sm" required placeholder="e.g. BPJS / JR - UMUM" id="insurance_icu">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Asal Ruangan</label>
                    <select name="floor" class="form-select form-select-sm" required>
                        @foreach(['ICU','ICCU','NICU','PICU'] as $r)
                        <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Ruang Bed</label>
                    <input type="text" name="room" class="form-control form-control-sm" required placeholder="e.g. ICU Bed 3">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Total Billing (Rp)</label>
                    <input type="number" name="total_bill" class="form-control form-control-sm" required placeholder="e.g. 29626664">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Tanggal Registrasi</label>
                    <input type="date" name="tgl_registrasi" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Tanggal Pulang / Aktual</label>
                    <input type="date" name="tgl_pulang" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Tanggal Discharge (Sistem)</label>
                    <input type="date" name="tgl_discharge" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Status Kepulangan</label>
                    <select name="status" class="form-select form-select-sm" required>
                        @foreach(['Pulang','Meninggal','Rujuk','Pindah Jaminan'] as $s)
                        <option value="{{ $s }}">{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Tindakan COT</label>
                    <select name="cot_status" class="form-select form-select-sm" required>
                        <option value="TIDAK">TIDAK</option>
                        <option value="YA">YA</option>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <label class="form-label fw-bold" style="font-size:0.78rem;">Keterangan / Catatan</label>
                <textarea name="keterangan_proses" class="form-control form-control-sm" rows="3" placeholder="ACC Gemi, Resep, atau kendala..."></textarea>
            </div>
            <div class="d-flex justify-content-end mt-4 border-top pt-3">
                <button type="submit" class="btn btn-primary btn-sm fw-bold text-white">Simpan Data ICU</button>
            </div>
        </form>
    </div>
</div>

{{-- COT FORM --}}
<div class="card card-rounded shadow-sm input-form-section d-none" id="form-cot">
    <div class="card-body">
        <h6 class="fw-bold mb-4">Form Entri Tindakan COT</h6>
        <form action="{{ route('adru.patients.store-cot') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">No. RM <span class="text-primary" style="font-size:0.7rem;">(Otomatis)</span></label>
                    <input type="text" name="rm" class="form-control form-control-sm" placeholder="e.g. 00298969" required id="rm_cot">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Nama Pasien <span id="dob_cot_info" class="text-muted fw-normal ms-2" style="font-size:0.7rem;"></span></label>
                    <input type="text" name="name" class="form-control form-control-sm" required placeholder="e.g. Suyatmin. TN" id="name_cot">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Dokter Operator</label>
                    <input type="text" name="doctor_operator" class="form-control form-control-sm" placeholder="e.g. dr. Heri Noviyanto, Sp.OG">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Jasa Operator (Rp)</label>
                    <input type="number" name="operator_fee" class="form-control form-control-sm" placeholder="e.g. 2500000">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Dokter Anestesi</label>
                    <input type="text" name="doctor_anestesi" class="form-control form-control-sm" placeholder="e.g. dr. Fithriatul, Sp.An">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Jasa Anestesi (Rp)</label>
                    <input type="number" name="anestesi_fee" class="form-control form-control-sm" placeholder="e.g. 1200000">
                </div>
            </div>
            <div class="d-flex justify-content-end mt-4 border-top pt-3">
                <button type="submit" class="btn btn-primary btn-sm fw-bold text-white">Simpan Tindakan COT</button>
            </div>
        </form>
    </div>
</div>

{{-- OUTSTANDING FORM --}}
<div class="card card-rounded shadow-sm input-form-section d-none" id="form-outstanding">
    <div class="card-body">
        <h6 class="fw-bold mb-4">Form Entri Outstanding Item</h6>
        <form action="{{ route('adru.outstanding.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Pasien (No. RM / Nama)</label>
                    <select name="admission_id" class="form-select form-select-sm" required>
                        <option value="">-- Pilih Pasien --</option>
                        @foreach($patients as $p)
                        <option value="{{ $p->id }}">{{ $p->rm }} - {{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Unit</label>
                    <select name="unit" class="form-select form-select-sm" required>
                        @foreach(['Farmasi','Laboratorium','Radiologi','Bedside','Admin OK','Tindakan'] as $u)
                        <option value="{{ $u }}">{{ $u }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Kategori</label>
                    <select name="category" class="form-select form-select-sm" required>
                        @foreach(['Alat Kesehatan','Tindakan Ranap'] as $c)
                        <option value="{{ $c }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Nama Item</label>
                    <input type="text" name="item_name" class="form-control form-control-sm" required placeholder="e.g. ETT No.7.5">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Harga (Rp)</label>
                    <input type="number" name="price" class="form-control form-control-sm" placeholder="e.g. 150000">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.78rem;">Tanggal Tindakan</label>
                    <input type="date" name="tgl_tindakan" class="form-control form-control-sm">
                </div>
            </div>
            <div class="d-flex justify-content-end mt-4 border-top pt-3">
                <button type="submit" class="btn btn-primary btn-sm fw-bold text-white">Simpan Outstanding Item</button>
            </div>
        </form>
    </div>
</div>
@stop

@section('js')
<script>
document.querySelectorAll('.input-type-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const type = this.dataset.type;
        // Update button styles
        document.querySelectorAll('.input-type-btn').forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-secondary');
        });
        this.classList.remove('btn-outline-secondary');
        this.classList.add('btn-primary');
        // Show correct form
        document.querySelectorAll('.input-form-section').forEach(f => f.classList.add('d-none'));
        document.getElementById('form-' + type).classList.remove('d-none');
    });
});

// RM auto-lookup (debounced) for multiple forms
let rmTimeout;
['ranap', 'icu', 'cot'].forEach(type => {
    document.getElementById(`rm_${type}`)?.addEventListener('input', function() {
        clearTimeout(rmTimeout);
        const val = this.value.trim();
        const infoSpan = document.getElementById(`dob_${type}_info`);
        
        // Reset info span
        if (infoSpan) infoSpan.textContent = '';

        if (val.length >= 6) {
            rmTimeout = setTimeout(() => {
                fetch('/patient/check-by-mrn?mrn=' + encodeURIComponent(val))
                    .then(r => r.json())
                    .then(data => {
                        if (data.exists) {
                            // Populate Name
                            const nameField = document.getElementById(`name_${type}`);
                            if (nameField) nameField.value = data.name || '';

                            // Populate Insurance/Penjamin if applicable
                            if (type === 'ranap' || type === 'icu') {
                                const insField = document.getElementById(`insurance_${type}`);
                                if (insField && data.penjamin) insField.value = data.penjamin;
                            }

                            // Show DOB / Gender info under helper span
                            if (infoSpan && (data.dob || data.gender)) {
                                let details = [];
                                if (data.dob) {
                                    // format YYYY-MM-DD to DD-MM-YYYY if formatted
                                    const parts = data.dob.split('-');
                                    const formattedDob = parts.length === 3 ? `${parts[2]}-${parts[1]}-${parts[0]}` : data.dob;
                                    details.push(`Lahir: ${formattedDob}`);
                                }
                                if (data.gender) {
                                    details.push(`JK: ${data.gender}`);
                                }
                                infoSpan.textContent = `(${details.join(', ')})`;
                            }

                            showToast('Pasien ditemukan: ' + data.name, 'success');
                        }
                    }).catch(() => {});
            }, 800);
        }
    });
});

function showToast(msg, type) {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed top-0 end-0 m-3 shadow-lg`;
    toast.style.cssText = 'z-index:9999;min-width:280px;';
    toast.innerHTML = `<i class="mdi mdi-${type === 'success' ? 'check-circle' : 'alert-circle'} me-2"></i>${msg}`;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}
</script>
@stop
