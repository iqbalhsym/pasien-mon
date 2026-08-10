@extends('layouts.staradmin')

@section('title', 'Import & Export')

@section('content_header')
<div class="row mb-4">
  <div class="col-sm-12">
    <div class="home-tab">
      <div class="d-sm-flex align-items-center justify-content-between border-bottom pb-3">
        <div>
          <h1 class="h2 text-dark font-weight-bold">Import & Export</h1>
        </div>
      </div>
    </div>
  </div>
</div>
@stop

@section('content')
<div class="row">
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <h4 class="card-title card-title-dash mb-4 text-primary">Import Data Pasien</h4>
                <p class="text-muted">Unduh template terlebih dahulu, isi data, lalu unggah kembali.</p>
                
                <div class="mb-4">
                    <a href="{{ route('adru.template', 'ranap') }}" class="btn btn-outline-primary btn-sm"><i class="mdi mdi-download"></i> Template Ranap</a>
                    <a href="{{ route('adru.template', 'icu') }}" class="btn btn-outline-danger btn-sm"><i class="mdi mdi-download"></i> Template ICU</a>
                </div>

                <form action="{{ route('adru.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label>Pilih File Excel</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                    </div>
                    <div class="form-group mt-2">
                        <label>Jenis Data</label>
                        <select name="type" class="form-control" required>
                            <option value="ranap">Ranap Reguler</option>
                            <option value="icu">ICU / Intensif</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success text-white mt-3"><i class="mdi mdi-upload"></i> Upload & Import</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6 grid-margin stretch-card">
        <div class="card card-rounded shadow-sm">
            <div class="card-body">
                <h4 class="card-title card-title-dash mb-4 text-primary">Export Laporan</h4>
                <p class="text-muted">Unduh data pasien yang ada di sistem dalam format Excel.</p>
                
                <div class="d-flex flex-column gap-3 mt-4">
                    <a href="{{ route('adru.export', 'ranap') }}" class="btn btn-primary text-white"><i class="mdi mdi-file-excel"></i> Export Data Ranap</a>
                    <a href="{{ route('adru.export', 'icu') }}" class="btn btn-danger text-white mt-2"><i class="mdi mdi-file-excel"></i> Export Data ICU</a>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
