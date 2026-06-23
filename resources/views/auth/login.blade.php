<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login - PASIEN JOURNEY RS Universitas Indonesia</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />

    <style>
        :root {
            --primary: #1F3BB3;
            --primary-light: #E0E7FF;
            --primary-hover: #162a8c;
            --primary-glow: rgba(31, 59, 179, 0.12);
            --secondary: #0D9488;
            --secondary-hover: #0F766E;
            --dark: #0F172A;
            --light-bg: #F8FAFC;
            --text-muted: #64748B;
            --border-color: #E2E8F0;
            --card-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.08);
            --input-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.02);
            --font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            font-family: var(--font-family);
            background-color: var(--light-bg);
            min-height: 100vh;
            color: var(--dark);
            overflow-x: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-wrapper {
            width: 100%;
            min-height: 100vh;
            display: flex;
        }

        /* Form Side (Left) */
        .form-pane {
            flex: 0 0 100%;
            max-width: 100%;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem 2rem;
            position: relative;
            z-index: 10;
            transition: all 0.3s ease;
        }

        @media (min-width: 992px) {
            .form-pane {
                flex: 0 0 42%;
                max-width: 42%;
                padding: 4rem 3.5rem;
                box-shadow: 15px 0 40px rgba(15, 23, 42, 0.02);
            }
        }

        @media (min-width: 1200px) {
            .form-pane {
                flex: 0 0 35%;
                max-width: 35%;
                padding: 5rem 4.5rem;
            }
        }

        /* Graphic/Visual Side (Right) */
        .visual-pane {
            display: none;
            flex: 1;
            position: relative;
            background-image: url('{{ asset('images/bg-gedung-rsui.jpeg') }}');
            background-size: cover;
            background-position: center;
            align-items: center;
            justify-content: center;
            padding: 4rem;
            overflow: hidden;
        }

        @media (min-width: 992px) {
            .visual-pane {
                display: flex;
            }
        }

        .visual-pane::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(31, 59, 179, 0.92) 0%, rgba(13, 148, 136, 0.88) 100%);
            z-index: 1;
        }

        .visual-content {
            position: relative;
            z-index: 2;
            color: #ffffff;
            max-width: 550px;
            width: 100%;
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Glassmorphism Widget Card */
        .glass-widget {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.3);
        }

        .glass-badge {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1.25rem;
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .feature-item:last-child {
            margin-bottom: 0;
        }

        .feature-icon-wrapper {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        /* Brand & Typography */
        .brand-logo-wrapper {
            margin-bottom: 2.5rem;
        }

        .form-title {
            font-weight: 800;
            font-size: 1.75rem;
            color: var(--dark);
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        .form-subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-bottom: 2rem;
            font-weight: 500;
        }

        /* Inputs & Labels */
        .form-label-custom {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-icon-custom {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: #A0AEC0;
            font-size: 1.25rem;
            transition: all 0.3s ease;
            z-index: 5;
            pointer-events: none;
        }

        .form-control-custom {
            width: 100%;
            padding: 0.95rem 1.25rem 0.95rem 3.25rem;
            font-size: 0.95rem;
            font-weight: 550;
            color: var(--dark);
            background-color: #F8FAFC;
            border: 1.5px solid #E2E8F0;
            border-radius: 14px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--input-shadow);
        }

        .form-control-custom::placeholder {
            color: #A0AEC0;
            font-weight: 400;
        }

        .form-control-custom:focus {
            background-color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px var(--primary-glow);
            outline: none;
        }

        .form-control-custom:focus+.input-icon-custom {
            color: var(--primary);
        }

        /* Captcha Module Styles */
        .captcha-module {
            background-color: #F8FAFC;
            border: 1.5px solid #E2E8F0;
            border-radius: 16px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .captcha-flex-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .captcha-img-container {
            height: 52px;
            flex-grow: 1;
            background: #ffffff;
            border: 1px solid #E2E8F0;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2px;
            box-shadow: var(--input-shadow);
        }

        .captcha-btn-refresh {
            width: 52px;
            height: 52px;
            background: #ffffff;
            border: 1.5px solid #E2E8F0;
            border-radius: 10px;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .captcha-btn-refresh:hover {
            color: var(--secondary);
            border-color: var(--secondary);
            background-color: #F0FDFA;
            transform: scale(1.05);
        }

        .captcha-btn-refresh:active {
            transform: scale(0.95);
        }

        /* Custom Checkbox */
        .form-check-custom {
            display: flex;
            align-items: center;
        }

        .form-check-input-custom {
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 6px;
            border: 1.5px solid #CBD5E1;
            margin-right: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .form-check-input-custom:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .form-check-label-custom {
            font-size: 0.9rem;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            user-select: none;
        }

        /* Alerts and Errors */
        .alert-custom {
            border: none;
            border-radius: 16px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .alert-custom-danger {
            background-color: #FEF2F2;
            color: #991B1B;
            border-left: 4px solid #EF4444;
        }

        .alert-custom-success {
            background-color: #ECFDF5;
            color: #065F46;
            border-left: 4px solid #10B981;
        }

        .alert-icon-custom {
            font-size: 1.5rem;
            line-height: 1;
            flex-shrink: 0;
        }

        .alert-title-custom {
            font-weight: 700;
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }

        .alert-body-custom {
            font-size: 0.85rem;
            opacity: 0.9;
            line-height: 1.4;
        }

        .error-message-inline {
            font-size: 0.825rem;
            font-weight: 700;
            color: #DC2626;
            margin-top: 0.35rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Buttons */
        .btn-submit-premium {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
            color: #ffffff;
            border: none;
            border-radius: 14px;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            width: 100%;
            transition: all 0.25s ease;
            box-shadow: 0 10px 20px -5px rgba(31, 59, 179, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .btn-submit-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px -5px rgba(31, 59, 179, 0.4);
            background: linear-gradient(135deg, #2b49c7 0%, var(--primary) 100%);
        }

        .btn-submit-premium:active {
            transform: translateY(0);
        }

        /* Footer Credits */
        .footer-credit {
            margin-top: auto;
            font-size: 0.8rem;
            color: var(--text-muted);
            text-align: center;
            font-weight: 500;
            padding-top: 2rem;
        }

        /* Animations */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .mdi-spin-custom {
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
        
        .blinking-light {
            width: 12px;
            height: 12px;
            background-color: #10B981; /* Hijau cerah */
            border-radius: 50%;
            box-shadow: 0 0 8px #10B981, 0 0 15px #10B981;
            animation: blink 1.5s infinite alternate;
        }

        @keyframes blink {
            from {
                opacity: 1;
                transform: scale(1);
            }
            to {
                opacity: 0.4;
                transform: scale(0.85);
            }
        }
    </style>
</head>

<body>
    <div class="login-wrapper">
        <!-- Left Side: Form Panel -->
        <div class="form-pane">
            <div class="brand-logo-wrapper">
                <img src="{{ asset('images/logo-rsui-nyamping.png') }}" alt="Logo RSUI" style="max-height: 52px;"
                    class="img-fluid">
            </div>

            <div class="mb-4">
                <h3 class="form-title">Halo, Selamat Datang!</h3>
                <p class="form-subtitle">Masuk dengan Akun</p>
            </div>

            <!-- Global Status Alerts -->
            @if (session('status'))
                <div class="alert-custom alert-custom-success" role="alert">
                    <i class="mdi mdi-check-circle-outline alert-icon-custom text-success"></i>
                    <div>
                        <div class="alert-title-custom">Berhasil</div>
                        <div class="alert-body-custom">{{ session('status') }}</div>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert-custom alert-custom-danger" role="alert">
                    <i class="mdi mdi-alert-circle-outline alert-icon-custom text-danger"></i>
                    <div>
                        <div class="alert-title-custom">Autentikasi Gagal</div>
                        <div class="alert-body-custom">Username, Kata Sandi, atau Verifikasi Captcha Anda salah. Silakan
                            coba kembali.</div>
                    </div>
                </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('login') }}" autocomplete="off">
                @csrf

                <!-- Username/Email Input -->
                <div class="mb-3">
                    <label for="username" class="form-label-custom">Username</label>
                    <div class="input-group-custom">
                        <input type="text" id="username" name="username" class="form-control-custom"
                            placeholder="username" value="{{ old('username') }}" required autofocus>
                        <i class="mdi mdi-account-outline input-icon-custom"></i>
                    </div>
                    @error('username')
                        <div class="error-message-inline">
                            <i class="mdi mdi-alert-circle"></i> {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Password Input -->
                <div class="mb-4">
                    <label for="password" class="form-label-custom">Kata Sandi</label>
                    <div class="input-group-custom">
                        <input type="password" id="password" name="password" class="form-control-custom"
                            placeholder="kata sandi" required>
                        <i class="mdi mdi-lock-outline input-icon-custom"></i>
                    </div>
                </div>

                <!-- Captcha Module -->
                <div class="captcha-module">
                    <label class="form-label-custom">Verifikasi Captcha</label>
                    <div class="captcha-flex-wrapper">
                        <div class="captcha-img-container">
                            <img id="captcha-img" src="{{ $captcha_image }}" alt="Captcha"
                                style="height: 100%; width: auto; object-fit: contain;">
                        </div>
                        <button type="button" class="captcha-btn-refresh" onclick="refreshCaptcha()"
                            title="Ganti Kode Soal">
                            <i class="mdi mdi-refresh fs-3"></i>
                        </button>
                    </div>

                    <div class="input-group-custom mb-0">
                        <input type="number" name="captcha" class="form-control-custom" placeholder="Masukkan jawaban"
                            required>
                        <i class="mdi mdi-calculator input-icon-custom"></i>
                    </div>
                    @error('captcha')
                        <div class="error-message-inline">
                            <i class="mdi mdi-alert-circle"></i> {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Remember Me Checkbox -->
                <div class="mb-4 d-flex align-items-center">
                    <div class="form-check-custom">
                        <input type="checkbox" name="remember" class="form-check-input form-check-input-custom"
                            id="remember">
                        <label class="form-check-label-custom" for="remember">Ingat sesi saya</label>
                    </div>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" class="btn-submit-premium">
                        <i class="mdi mdi-login fs-5"></i> MASUK SISTEM
                    </button>
                </div>
            </form>

            <!-- Footer Credit -->
            <div class="footer-credit">
                &copy; 2026 RS Universitas Indonesia. IT & SIMRS.
            </div>
        </div>

        <!-- Right Side: Graphic Visual Pane (Shown on large screens) -->
        <div class="visual-pane">
            <div class="visual-content">
                <div class="glass-widget">
                    {{-- <span class="glass-badge">
                        <i class="mdi mdi-shield-check-outline"></i> Active Directory Secured
                    </span> --}}
                    <h2 class="fw-extrabold mb-3 text-center" style="font-size: 2.2rem; font-weight: 800; line-height: 1.25;">PASIEN JOURNEY</h2>
                    <p class="mb-4 text-justify" style="font-size: 1.05rem; opacity: 0.9; line-height: 1.6; font-weight: 500; text-align: justify;">
                        Portal pemantauan internal RS Universitas Indonesia untuk merekam, memelihara, dan meninjau seluruh data riwayat medis pasien secara terintegrasi, aman, dan efisien.
                    </p>

                    <div class="d-flex align-items-center justify-content-center mt-3" style="background: rgba(0,0,0,0.15); border-radius: 50px; padding: 0.5rem 1rem; width: max-content; margin: 0 auto; border: 1px solid rgba(255,255,255,0.1);">
                        <div class="blinking-light me-2"></div>
                        <span style="font-size: 0.9rem; font-weight: 600; letter-spacing: 0.5px; color: #fff;">Sistem aktif &mdash; Data real-time</span>
                    </div>

                    {{-- <hr style="border-top: 1px solid rgba(255,255,255,0.25); margin: 2rem 0;"> --}}

                    {{-- <div class="features-list">
                        <div class="feature-item">
                            <div class="feature-icon-wrapper">
                                <i class="mdi mdi-file-clock-outline"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1" style="font-size: 1rem;">Pemantauan Riwayat Terintegrasi</h6>
                                <p class="mb-0" style="font-size: 0.85rem; opacity: 0.8;">Akses cepat riwayat penanganan dan histori pasien.</p>
                            </div>
                        </div>

                        <div class="feature-item">
                            <div class="feature-icon-wrapper">
                                <i class="mdi mdi-security"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1" style="font-size: 1rem;">Sistem Keamanan LDAP Ganda</h6>
                                <p class="mb-0" style="font-size: 0.85rem; opacity: 0.8;">Autentikasi terpusat yang aman dengan dukungan cadangan login darurat lokal.</p>
                            </div>
                        </div>

                        <div class="feature-item">
                            <div class="feature-icon-wrapper">
                                <i class="mdi mdi-qrcode-scan"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1" style="font-size: 1rem;">QR Code Tracking Instan</h6>
                                <p class="mb-0" style="font-size: 0.85rem; opacity: 0.8;">Scan instan pada alat medis untuk mengunggah rekam medis dan data kalibrasi.</p>
                            </div>
                        </div>
                    </div> --}}
                </div>
            </div>
        </div>
    </div>

    <!-- Captcha Refresh Script -->
    <script>
        function refreshCaptcha() {
            const btn = document.querySelector('.captcha-btn-refresh i');
            btn.classList.add('mdi-spin-custom');

            fetch('{{ route('captcha.refresh') }}')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('captcha-img').src = data.captcha_image;
                    btn.classList.remove('mdi-spin-custom');
                })
                .catch(error => {
                    console.error('Error refreshing captcha:', error);
                    btn.classList.remove('mdi-spin-custom');
                });
        }
    </script>
</body>

</html>
