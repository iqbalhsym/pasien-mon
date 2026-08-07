@extends('layouts.staradmin')

@section('title', 'Manajemen Data Dokter')

@section('content_header')
<div class="row align-items-center mb-4">
    <div class="col-sm-8 d-flex align-items-center">
        <a href="{{ route('dashboard') }}" class="btn btn-light border shadow-sm px-3 py-2 me-3" title="Kembali ke Dashboard">
            <i class="mdi mdi-arrow-left fs-4 text-dark"></i>
        </a>
        <div>
            <h2 class="h2 text-dark font-weight-bold mb-1">
                <i class="mdi mdi-doctor text-primary me-2"></i> Manajemen Data Dokter
            </h2>
            <p class="text-muted mb-0" style="font-size:0.95rem;">
                Kelola daftar nama DPJP (Dokter Penanggung Jawab Pelayanan) dan Kelompok Staf Medis (KSM).
            </p>
        </div>
    </div>
</div>
@stop

@section('content')
<div class="row">
    @if(session('success'))
        <div class="col-12">
            <div class="alert alert-success d-flex align-items-center fw-bold shadow-sm mb-4">
                <i class="mdi mdi-check-circle fs-3 me-3"></i> {{ session('success') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="col-12">
            <div class="alert alert-danger d-flex align-items-center fw-bold shadow-sm mb-4">
                <i class="mdi mdi-alert fs-3 me-3"></i> {{ session('error') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="col-12">
            <div class="alert alert-danger shadow-sm mb-4">
                <ul class="mb-0 fw-bold">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Form Tambah / Edit Dokter -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0" id="formCard" style="border-top: 4px solid #1F3BB3 !important; border-radius: 8px;">
            <div class="card-body">
                <h4 class="card-title fw-bold text-primary mb-3" id="formTitle">TAMBAH DOKTER BARU</h4>
                
                <form id="doctorForm" action="{{ route('doctors.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">
                    
                    <div class="mb-3">
                        <label for="doctorName" class="form-label fw-bold">Nama Lengkap & Gelar Dokter</label>
                        <input type="text" name="name" id="doctorName" class="form-control text-dark fw-bold" required placeholder="Contoh: dr. John Doe, Sp.PD">
                    </div>

                    <div class="mb-3">
                        <label for="doctorKsm" class="form-label fw-bold">KSM / Spesialisasi</label>
                        <input type="text" name="ksm" id="doctorKsm" class="form-control text-dark fw-bold" required list="ksmOptions" placeholder="Pilih atau ketik KSM">
                        <datalist id="ksmOptions">
                            <option value="Penyakit Dalam">
                            <option value="Obstetri & Ginekologi">
                            <option value="Bedah">
                            <option value="Jantung">
                            <option value="Anestesi">
                            <option value="Anak">
                            <option value="Saraf">
                            <option value="Paru">
                            <option value="THT">
                            <option value="Mata">
                            <option value="Kulit & Kelamin">
                            <option value="Jiwa">
                            <option value="Rehabilitasi Medik">
                            <option value="Radiologi">
                            <option value="Patologi Klinik">
                            <option value="Umum">
                        </datalist>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary fw-bold flex-grow-1" id="submitBtn">Simpan Dokter</button>
                        <button type="button" class="btn btn-secondary fw-bold" id="cancelBtn" style="display: none;" onclick="resetForm()">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Dokter -->
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-0" style="border-top: 4px solid #6c757d !important; border-radius: 8px;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title fw-bold text-dark mb-0">DAFTAR DOKTER / DPJP</h4>
                    <span class="badge bg-primary text-white px-3 py-2">Total: {{ $doctors->count() }} Orang</span>
                </div>

                <!-- Live Search Bar -->
                <div class="mb-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="mdi mdi-magnify text-muted"></i></span>
                        <input type="text" id="doctorSearch" class="form-control bg-light border-start-0 text-dark fw-semibold" placeholder="Cari nama dokter atau KSM...">
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-hover border-top" id="doctorTable">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th class="text-white w-45">NAMA DOKTER</th>
                                <th class="text-white w-35">KSM / SPESIALISASI</th>
                                <th class="text-white text-center w-20">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($doctors as $doctor)
                            <tr class="doctor-row">
                                <td class="fw-bold text-dark doctor-name">
                                    <i class="mdi mdi-account-star text-primary me-2"></i>
                                    {{ $doctor->name }}
                                </td>
                                <td class="fw-semibold text-muted doctor-ksm">
                                    {{ $doctor->ksm }}
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <!-- Edit Button (updates the form card on the left) -->
                                        <button type="button" class="btn btn-warning btn-sm px-2 text-white fw-bold" 
                                                title="Edit"
                                                onclick="editDoctor('{{ $doctor->id }}', '{{ addslashes($doctor->name) }}', '{{ addslashes($doctor->ksm) }}')">
                                            <i class="mdi mdi-pencil"></i>
                                        </button>
                                        <!-- Delete Button -->
                                        <form action="{{ route('doctors.destroy', $doctor->id) }}" method="POST"
                                              onsubmit="return confirm('Hapus data Dokter {{ addslashes($doctor->name) }} dari sistem?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm px-2" title="Hapus">
                                                <i class="mdi mdi-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    Belum ada data Dokter terdaftar. Tambahkan data di form sebelah kiri.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Live Search Logic
    document.getElementById('doctorSearch').addEventListener('keyup', function() {
        const query = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#doctorTable .doctor-row');
        
        rows.forEach(row => {
            const name = row.querySelector('.doctor-name').innerText.toLowerCase();
            const ksm = row.querySelector('.doctor-ksm').innerText.toLowerCase();
            
            if (name.includes(query) || ksm.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    function editDoctor(id, name, ksm) {
        // Change Form Title & border to edit theme (warning / orange)
        document.getElementById('formTitle').innerText = 'EDIT DATA DOKTER';
        document.getElementById('formCard').style.borderTopColor = '#ffc107';
        
        // Populate inputs
        document.getElementById('doctorName').value = name;
        document.getElementById('doctorKsm').value = ksm;
        
        // Show Cancel button
        document.getElementById('cancelBtn').style.display = 'inline-block';
        
        // Set form action and method to PUT
        const form = document.getElementById('doctorForm');
        form.action = `{{ url('/doctors') }}/${id}`;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('submitBtn').innerText = 'Simpan Perubahan';
        
        // Focus the name field
        document.getElementById('doctorName').focus();
    }

    function resetForm() {
        // Reset Title & border to add theme
        document.getElementById('formTitle').innerText = 'TAMBAH DOKTER BARU';
        document.getElementById('formCard').style.borderTopColor = '#1F3BB3';
        
        // Clear inputs
        document.getElementById('doctorName').value = '';
        document.getElementById('doctorKsm').value = '';
        
        // Hide Cancel button
        document.getElementById('cancelBtn').style.display = 'none';
        
        // Set form action and method to POST
        const form = document.getElementById('doctorForm');
        form.action = `{{ route('doctors.store') }}`;
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('submitBtn').innerText = 'Simpan Dokter';
    }
</script>
@stop
