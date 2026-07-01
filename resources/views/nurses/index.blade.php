@extends('layouts.staradmin')

@section('title', 'Manajemen Data Ners')

@section('content_header')
<div class="row align-items-center mb-4">
    <div class="col-sm-8 d-flex align-items-center">
        <a href="{{ route('dashboard') }}" class="btn btn-light border shadow-sm px-3 py-2 me-3" title="Kembali ke Dashboard">
            <i class="mdi mdi-arrow-left fs-4 text-dark"></i>
        </a>
        <div>
            <h2 class="h2 text-dark font-weight-bold mb-1">
                <i class="mdi mdi-account-group text-primary me-2"></i> Manajemen Data Ners
            </h2>
            <p class="text-muted mb-0" style="font-size:0.95rem;">
                Kelola daftar nama Ners/Perawat bertugas yang akan digunakan dalam menu Monitoring Bed & Kamar.
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

    <!-- Form Tambah / Edit Ners -->
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-0" id="formCard" style="border-top: 4px solid #1F3BB3 !important; border-radius: 8px;">
            <div class="card-body">
                <h4 class="card-title fw-bold text-primary mb-3" id="formTitle">TAMBAH NERS BARU</h4>
                
                <form id="nurseForm" action="{{ route('nurses.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="formMethod" value="POST">
                    
                    <div class="mb-3">
                        <label for="nurseName" class="form-label fw-bold">Nama Lengkap & Gelar Ners</label>
                        <input type="text" name="name" id="nurseName" class="form-control text-dark fw-bold" required placeholder="Contoh: Ns. Jane Doe, S.Kep">
                    </div>

                    <div class="mb-3" id="statusContainer" style="display: none;">
                        <label for="nurseActive" class="form-label fw-bold">Status Keaktifan</label>
                        <select name="is_active" id="nurseActive" class="form-select text-dark fw-bold">
                            <option value="1">Aktif (Tampil di Dropdown)</option>
                            <option value="0">Non-Aktif (Sembunyikan)</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary fw-bold flex-grow-1" id="submitBtn">Simpan Ners</button>
                        <button type="button" class="btn btn-secondary fw-bold" id="cancelBtn" style="display: none;" onclick="resetForm()">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Ners -->
    <div class="col-md-8 mb-4">
        <div class="card shadow-sm border-0" style="border-top: 4px solid #6c757d !important; border-radius: 8px;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title fw-bold text-dark mb-0">DAFTAR NERS / PERAWAT</h4>
                    <span class="badge bg-primary text-white px-3 py-2">Total: {{ $nurses->count() }} Orang</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover border-top">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th class="text-white w-50">NAMA NERS</th>
                                <th class="text-white text-center">STATUS</th>
                                <th class="text-white text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($nurses as $nurse)
                            <tr>
                                <td class="fw-bold text-dark">
                                    <i class="mdi mdi-account-star text-primary me-2"></i>
                                    {{ $nurse->name }}
                                </td>
                                <td class="text-center">
                                    @if($nurse->is_active)
                                        <span class="badge bg-success text-white px-2.5 py-1.5 fw-bold">
                                            <i class="mdi mdi-check-circle me-1"></i> Aktif
                                        </span>
                                    @else
                                        <span class="badge bg-secondary text-white px-2.5 py-1.5 fw-bold">
                                            <i class="mdi mdi-close-circle me-1"></i> Non-Aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-2 justify-content-center">
                                        <!-- Edit Button (updates the form card on the left) -->
                                        <button type="button" class="btn btn-warning btn-sm px-2 text-white fw-bold" 
                                                title="Edit"
                                                onclick="editNurse('{{ $nurse->id }}', '{{ addslashes($nurse->name) }}', '{{ $nurse->is_active }}')">
                                            <i class="mdi mdi-pencil"></i>
                                        </button>
                                        <!-- Delete Button -->
                                        <form action="{{ route('nurses.destroy', $nurse->id) }}" method="POST"
                                              onsubmit="return confirm('Hapus data Ners {{ addslashes($nurse->name) }} dari sistem?');">
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
                                    Belum ada data Ners terdaftar. Tambahkan data di form sebelah kiri.
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
    function editNurse(id, name, isActive) {
        // Change Form Title & border to edit theme (warning / orange)
        document.getElementById('formTitle').innerText = 'EDIT DATA NERS';
        document.getElementById('formCard').style.borderTopColor = '#ffc107';
        
        // Populate inputs
        document.getElementById('nurseName').value = name;
        document.getElementById('nurseActive').value = isActive;
        
        // Show status selector and Cancel button
        document.getElementById('statusContainer').style.display = 'block';
        document.getElementById('cancelBtn').style.display = 'inline-block';
        
        // Set form action and method to PUT
        const form = document.getElementById('nurseForm');
        form.action = `{{ url('/nurses') }}/${id}`;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('submitBtn').innerText = 'Simpan Perubahan';
        
        // Focus the name field
        document.getElementById('nurseName').focus();
    }

    function resetForm() {
        // Reset Title & border to add theme
        document.getElementById('formTitle').innerText = 'TAMBAH NERS BARU';
        document.getElementById('formCard').style.borderTopColor = '#1F3BB3';
        
        // Clear inputs
        document.getElementById('nurseName').value = '';
        document.getElementById('nurseActive').value = '1';
        
        // Hide status selector and Cancel button
        document.getElementById('statusContainer').style.display = 'none';
        document.getElementById('cancelBtn').style.display = 'none';
        
        // Set form action and method to POST
        const form = document.getElementById('nurseForm');
        form.action = `{{ route('nurses.store') }}`;
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('submitBtn').innerText = 'Simpan Ners';
    }
</script>
@stop
