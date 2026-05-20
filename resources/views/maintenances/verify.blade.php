<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Keamanan - {{ $equipment->merk }}</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- MDI Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css">
    <style>
        body {
            background-color: #f4f7f9;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .verify-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
            border: none;
            width: 100%;
            max-width: 480px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .verify-header {
            background: linear-gradient(135deg, #1F3BB3 0%, #0d6efd 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        .verify-icon {
            font-size: 4rem;
            line-height: 1;
            margin-bottom: 10px;
            display: inline-block;
            animation: pulse 2s infinite;
        }
        .verify-body {
            padding: 40px 35px;
        }
        .form-control-lg {
            border-radius: 12px;
            font-size: 1.1rem;
            padding: 12px 20px;
            border: 2px solid #e2e8f0;
        }
        .form-control-lg:focus {
            border-color: #1F3BB3;
            box-shadow: 0 0 0 3px rgba(31, 59, 179, 0.15);
        }
        .btn-verify {
            background: linear-gradient(135deg, #1F3BB3 0%, #0d6efd 100%);
            border: none;
            color: white;
            padding: 14px 28px;
            font-weight: 700;
            border-radius: 12px;
            font-size: 1.1rem;
            width: 100%;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(31, 59, 179, 0.2);
        }
        .btn-verify:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            box-shadow: 0 7px 20px rgba(31, 59, 179, 0.3);
        }
        .logo-rs {
            width: 50px;
            margin-bottom: 15px;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>

    <div class="verify-card">
        <div class="verify-header">
            <img src="{{ asset('images/favicon-icon.png') }}" alt="Logo" class="logo-rs" onerror="this.style.display='none'">
            <div class="verify-icon">
                <i class="mdi mdi-shield-lock-outline"></i>
            </div>
            <h2 class="h4 fw-bold mb-1">Verifikasi Keamanan</h2>
            <p class="text-white-50 mb-0" style="font-size: 0.95rem;">Silakan verifikasi identitas untuk melihat rekam medis pasien.</p>
        </div>
        
        <div class="verify-body">
            <!-- Info Singkat Pasien -->
            <div class="bg-light p-3 rounded-3 mb-4 text-center">
                <div class="small text-muted fw-bold">NO. REKAM MEDIS (RM)</div>
                <div class="h5 fw-bold text-dark mb-0">{{ $equipment->serial_number }}</div>
            </div>

            @if($errors->any())
                <div class="alert alert-danger d-flex align-items-center fw-bold p-3 mb-4 rounded-3 border-0" style="font-size: 0.95rem;">
                    <i class="mdi mdi-alert-circle fs-4 me-2"></i>
                    <div>{{ $errors->first('tanggal_lahir') }}</div>
                </div>
            @endif

            <form action="{{ route('alat.public', $equipment->serial_number) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label for="tanggal_lahir" class="form-label fw-bold text-secondary" style="font-size: 0.95rem;">
                        <i class="mdi mdi-calendar-range me-1"></i> MASUKKAN TANGGAL LAHIR PASIEN:
                    </label>
                    <input type="date" name="tanggal_lahir" id="tanggal_lahir" class="form-control form-control-lg fw-bold" required>
                    <div class="form-text text-muted mt-2">
                        *Demi privasi pasien, Anda wajib memverifikasi tanggal lahir pasien terdaftar untuk mengakses rekam medis ini.
                    </div>
                </div>

                <button type="submit" class="btn btn-verify">
                    <i class="mdi mdi-lock-open-outline me-1"></i> VERIFIKASI & BUKA RIWAYAT PEMERIKSAAN
                </button>
            </form>
        </div>
    </div>

</body>
</html>
