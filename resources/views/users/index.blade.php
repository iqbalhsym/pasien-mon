@extends('layouts.staradmin')

@section('title', 'Manajemen User')

@section('content_header')
<div class="row align-items-center mb-4">
    <div class="col-sm-8 d-flex align-items-center">
        <a href="{{ route('dashboard') }}" class="btn btn-light border shadow-sm px-3 py-2 me-3" title="Kembali ke Dashboard">
            <i class="mdi mdi-arrow-left fs-4 text-dark"></i>
        </a>
        <div>
            <h2 class="h2 text-dark font-weight-bold mb-1">
                <i class="mdi mdi-account-group text-primary me-2"></i> Pengelolaan Akun Pengguna
            </h2>
            <p class="text-muted mb-0" style="font-size:0.95rem;">
                @if(auth()->user()->role === 'admin')
                    Daftar akun yang telah login melalui Active Directory RSUI. Atur peran akses setiap pengguna.
                @else
                    Anda dapat melihat daftar pengguna dan mengubah akun <strong>View Only</strong> menjadi <strong>Editor</strong>.
                @endif
            </p>
        </div>
    </div>
</div>
@stop

@section('content')
<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">

        @if(session('success'))
            <div class="alert alert-success d-flex align-items-center fw-bold w-100 shadow-sm">
                <i class="mdi mdi-check-circle fs-3 me-3"></i> {{ session('success') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger d-flex align-items-center fw-bold w-100 shadow-sm">
                <i class="mdi mdi-alert fs-3 me-3"></i> {{ session('error') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card shadow-sm border-0" style="border-top: 4px solid #1F3BB3 !important;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title fw-bold text-primary mb-0">DAFTAR PENGGUNA AKTIF (AD)</h4>
                    <span class="badge bg-primary text-white px-3 py-2">Total: {{ $users->count() }} Akun</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover border-top">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th class="text-white">NAMA & USERNAME</th>
                                <th class="text-white">EMAIL</th>
                                <th class="text-white">PERAN / ROLE</th>
                                <th class="text-white">TUGAS LANTAI</th>
                                <th class="text-white">TERAKHIR LOGIN</th>
                                <th class="text-white text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark">{{ $user->name }}</div>
                                    <div class="text-muted" style="font-size:0.82rem;">
                                        <i class="mdi mdi-account-circle me-1"></i>{{ $user->username ?? '-' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="text-muted">{{ $user->email ?? '-' }}</div>
                                </td>
                                <td>
                                    @if($user->role == 'admin')
                                        <span class="badge bg-primary text-white px-3 py-2">
                                            <i class="mdi mdi-shield-account me-1"></i> Administrator
                                        </span>
                                    @elseif($user->role == 'user')
                                        <span class="badge bg-success text-white px-3 py-2">
                                            <i class="mdi mdi-account-edit me-1"></i> Editor
                                        </span>
                                    @else
                                        <span class="badge bg-secondary text-white px-3 py-2">
                                            <i class="mdi mdi-eye me-1"></i> View Only
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->role === 'admin')
                                        <span class="badge bg-light text-muted border px-2.5 py-1.5 fw-bold">Semua Lantai</span>
                                    @elseif($user->floor)
                                        <span class="badge bg-info text-white px-2.5 py-1.5 fw-bold">
                                            Lantai {{ $user->floor }}
                                        </span>
                                    @else
                                        <span class="badge bg-light text-dark border px-2.5 py-1.5 fw-bold">Semua Lantai</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="text-dark" style="font-size:0.88rem;">
                                        <i class="mdi mdi-calendar-check text-success me-1"></i>
                                        {{ $user->updated_at->format('d/m/Y H:i') }}
                                    </div>
                                </td>
                                <td class="text-center align-middle">
                                    @if($user->id !== auth()->id())

                                        {{-- TAMPILAN UNTUK ADMIN: Kontrol penuh --}}
                                        @if(auth()->user()->role === 'admin')
                                            <div class="d-flex gap-2 justify-content-center">
                                                <form action="{{ route('users.updateRole', $user->id) }}" method="POST" class="d-flex gap-1 align-items-center">
                                                    @csrf
                                                    @method('PUT')
                                                    <select name="role" class="form-select form-select-sm" style="width:110px; font-size:0.82rem;">
                                                        <option value="viewer" {{ $user->role == 'viewer' ? 'selected' : '' }}>View Only</option>
                                                        <option value="user"   {{ $user->role == 'user'   ? 'selected' : '' }}>Editor</option>
                                                        <option value="admin"  {{ $user->role == 'admin'  ? 'selected' : '' }}>Admin</option>
                                                    </select>
                                                    <select name="floor" class="form-select form-select-sm" style="width:120px; font-size:0.82rem;">
                                                        <option value="">Semua Lantai</option>
                                                        @foreach($globalFloors as $fl)
                                                            @php
                                                                $flName = $fl->name;
                                                                if (preg_match('/Lantai\s+(\d+)/i', $flName, $matches)) {
                                                                    $flName = $matches[1];
                                                                }
                                                            @endphp
                                                            <option value="{{ $flName }}" {{ $user->floor == $flName ? 'selected' : '' }}>
                                                                Lantai {{ $flName }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-primary btn-sm px-2" title="Simpan Akses">
                                                        <i class="mdi mdi-content-save"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                                      onsubmit="return confirm('Hapus akun {{ $user->name }} dari sistem?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm px-2" title="Hapus">
                                                        <i class="mdi mdi-trash-can"></i>
                                                    </button>
                                                </form>
                                            </div>

                                        {{-- TAMPILAN UNTUK EDITOR: Hanya bisa promote viewer → editor --}}
                                        @elseif(auth()->user()->role === 'user')
                                            @if($user->role === 'viewer')
                                                <form action="{{ route('users.promote', $user->id) }}" method="POST"
                                                      onsubmit="return confirm('Jadikan {{ $user->name }} sebagai Editor?');">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-success btn-sm px-3 fw-bold">
                                                        <i class="mdi mdi-account-arrow-up me-1"></i> Jadikan Editor
                                                    </button>
                                                </form>
                                            @elseif($user->role === 'admin')
                                                <span class="badge bg-primary text-white px-3 py-2">
                                                    <i class="mdi mdi-lock me-1"></i> Terkunci
                                                </span>
                                            @else
                                                <span class="badge bg-light text-muted border px-3 py-2">
                                                    <i class="mdi mdi-check me-1"></i> Sudah Editor
                                                </span>
                                            @endif
                                        @endif

                                    @else
                                        <span class="badge bg-info text-white px-3 py-2">
                                            <i class="mdi mdi-account-check me-1"></i> Akun Ini
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="mdi mdi-account-off fs-2 d-block mb-2"></i>
                                    Belum ada pengguna yang login melalui AD.
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
@stop
