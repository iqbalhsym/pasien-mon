<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>@yield('title', 'PASIEN JOURNEY') - RS Universitas Indonesia</title>

  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Material Design Icons CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">

  <!-- Google Fonts for Star Admin vibe -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />

  <style>
    /* Star Admin 2 Core Overrides */
    body {
      font-family: 'Manrope', sans-serif;
      font-size: 0.95rem;
      background-color: #f4f5f7;
      color: #1F2937;
      overflow-x: hidden;
    }

    /* Global Resizer */
    .fs-5 {
      font-size: 1rem !important;
    }

    .fs-4 {
      font-size: 1.15rem !important;
    }

    h2,
    .h2 {
      font-size: 1.5rem !important;
    }

    h4,
    .h4 {
      font-size: 1.15rem !important;
    }

    h5,
    .h5 {
      font-size: 1rem !important;
    }

    /* Typography */
    .h1,
    .h2,
    .h3,
    .h4,
    h1,
    h2,
    h3,
    h4 {
      font-weight: 700;
      color: #1F2937;
    }

    .text-primary {
      color: #1F3BB3 !important;
    }

    .bg-primary {
      background-color: #1F3BB3 !important;
    }

    .btn-primary {
      background-color: #1F3BB3;
      border-color: #1F3BB3;
    }

    .btn-primary:hover {
      background-color: #182e8f;
      border-color: #182e8f;
    }

    /* Layout Components */
    .container-scroller {
      overflow-x: hidden;
    }

    .page-body-wrapper {
      min-height: 100vh;
      display: flex;
      flex-direction: row;
      padding-top: 60px;
    }

    /* =====================
       NAVBAR — FIX STICKY
       ===================== */
    .navbar {
      background: #ffffff;
      box-shadow: 0px 4px 16px rgba(0, 0, 0, 0.08);
      height: 60px;
      position: fixed;
      top: 0;
      right: 0;
      left: 0;
      z-index: 1050;
      /* Lebih tinggi dari semua elemen */
      padding: 0;
      display: flex;
      align-items: center;
    }

    .navbar .navbar-brand-wrapper {
      width: 250px;
      min-width: 250px;
      height: 60px;
      background: #ffffff;
      display: flex;
      align-items: center;
      padding: 0 1.5rem;
      flex-shrink: 0;
    }

    .navbar-menu-wrapper {
      flex-grow: 1;
      display: flex;
      align-items: center;
      padding: 0 1rem;
      overflow: hidden;
      /* Cegah overflow
      */
    }

    .navbar-menu-wrapper .navbar-nav {
      flex-wrap: nowrap;
      align-items: center;
    }

    .navbar-brand {
      font-weight: 800;
      font-size: 1.1rem;
      color: #1F3BB3 !important;
      letter-spacing: -0.5px;
      white-space: nowrap;
    }

    /* Tombol Keluar — pastikan tidak terpotong */
    .navbar .btn-logout {
      white-space: nowrap;
      flex-shrink: 0;
    }

    /* =====================
       SIDEBAR
       ===================== */
    .sidebar {
      min-height: calc(100vh - 60px);
      background: #ffffff;
      width: 250px;
      position: fixed;
      top: 60px;
      bottom: 0;
      left: 0;
      z-index: 1040;
      box-shadow: 4px 0px 16px rgba(0, 0, 0, 0.06);
      transition: transform 0.25s ease;
      overflow-y: auto;
      overflow-x: hidden;
    }

    .sidebar .nav {
      flex-direction: column;
      padding: 1rem 0;
    }

    .sidebar .nav .nav-item {
      padding: 0 1.5rem;
      margin-bottom: 0.5rem;
    }

    .sidebar .nav .nav-item .nav-link {
      display: flex;
      align-items: center;
      padding: 0.8rem 1.25rem;
      color: #6c7383;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.95rem;
      transition: all 0.2s ease;
      white-space: nowrap;
    }

    .sidebar .nav .nav-item .nav-link i.mdi {
      font-size: 1.25rem;
      margin-right: 15px;
      color: #b9b9b9;
      flex-shrink: 0;
    }

    .sidebar .nav .nav-item.active .nav-link,
    .sidebar .nav .nav-item .nav-link:hover {
      background: #1F3BB3;
      color: #ffffff;
    }

    .sidebar .nav .nav-item.active .nav-link i.mdi,
    .sidebar .nav .nav-item .nav-link:hover i.mdi {
      color: #ffffff;
    }

    .sidebar .category-heading {
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      color: #8D94A5;
      margin: 1.5rem 1.5rem 0.5rem;
      letter-spacing: 0.5px;
    }

    /* =====================
       MAIN PANEL
       ===================== */
    .main-panel {
      width: calc(100% - 250px);
      margin-left: 250px;
      display: flex;
      flex-direction: column;
      min-height: calc(100vh - 60px);
      transition: all 0.25s ease;
    }

    .content-wrapper {
      background: #F4F5F7;
      padding: 1.5rem;
      flex-grow: 1;
    }

    /* Cards */
    .card {
      border: none;
      border-radius: 12px;
      box-shadow: 0px 4px 16px rgba(0, 0, 0, 0.04);
      margin-bottom: 1.5rem;
    }

    .card .card-body {
      padding: 1.5rem;
    }

    .card .card-title {
      color: #1F2937;
      margin-bottom: 1.2rem;
      text-transform: capitalize;
      font-size: 1.1rem;
      font-weight: 700;
    }

    /* Tables */
    .table-responsive {
      border-radius: 8px;
      -webkit-overflow-scrolling: touch;
    }

    .table th {
      border-top: none;
      font-weight: 700;
      text-transform: uppercase;
      font-size: 0.82rem;
      color: #8D94A5;
      padding: 0.85rem 1rem;
      white-space: nowrap;
    }

    .table td {
      padding: 0.85rem 1rem;
      vertical-align: middle;
      font-size: 0.9rem;
      color: #1F2937;
      font-weight: 500;
      border-top: 1px solid #f3f3f3;
    }

    /* Form Controls */
    .form-control,
    .form-select {
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 0.65rem 1rem;
      font-size: 0.95rem;
      font-weight: 500;
      color: #1F2937;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: #1F3BB3;
      box-shadow: 0 0 0 0.2rem rgba(31, 59, 179, 0.1);
    }

    .form-label {
      font-weight: 600;
      color: #1F2937;
      margin-bottom: 0.5rem;
      font-size: 0.9rem;
    }

    /* Badges */
    .badge {
      padding: 0.4rem 0.7rem;
      font-weight: 700;
      border-radius: 6px;
      font-size: 0.78rem;
    }

    /* Buttons */
    .btn {
      font-weight: 600;
      padding: 0.55rem 1.25rem;
      border-radius: 8px;
      font-size: 0.9rem;
      touch-action: manipulation;
      /* Fix: agar tombol responsif di mobile */
    }

    /* Modals */
    .modal-content {
      border: none;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .modal-header {
      border-bottom: 1px solid #f3f3f3;
      padding: 1.25rem 1.5rem;
    }

    .modal-body {
      padding: 1.5rem;
    }

    .modal-footer {
      border-top: 1px solid #f3f3f3;
      padding: 1.25rem 1.5rem;
    }

    /* Footer */
    .footer {
      background: #ffffff;
      padding: 16px 1.5rem;
      border-top: 1px solid #e9eaee;
      font-size: 0.88rem;
      font-weight: 500;
      color: #6c7383;
    }

    /* =====================
       OVERLAY MOBILE
       ===================== */
    .sidebar-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1039;
    }

    .sidebar-overlay.active {
      display: block;
    }

    /* =====================
       RESPONSIVE — MOBILE
       ===================== */
    @media (max-width: 991.98px) {

      /* Sidebar: hidden off-canvas */
      .sidebar {
        transform: translateX(-250px);
        z-index: 1045;
      }

      .sidebar.active {
        transform: translateX(0);
      }

      /* Main panel: full width */
      .main-panel {
        width: 100%;
        margin-left: 0;
      }

      /* Navbar brand: lebih kecil */
      .navbar .navbar-brand-wrapper {
        width: auto;
        min-width: unset;
        padding: 0 0.75rem;
      }

      .navbar-brand span {
        display: none;
        /* Sembunyikan teks, hanya ikon */
      }

      /* Content padding lebih kecil */
      .content-wrapper {
        padding: 1rem;
      }

      /* Header baris pada mobile: stack vertikal */
      .row.align-items-center.mb-4>div {
        width: 100%;
      }

      /* Tombol tambah full width di mobile */
      .content-wrapper .btn-primary.fw-bold.px-4 {
        width: 100%;
        justify-content: center;
      }

      /* Search box full width */
      .card-body .w-25 {
        width: 100% !important;
        margin-top: 0.75rem;
      }

      /* Header card: stack vertikal */
      .card-body .d-flex.justify-content-between {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 0.75rem;
      }

      /* Tabel: pastikan bisa scroll horizontal */
      .table-responsive {
        overflow-x: auto;
      }

      .table {
        min-width: 600px;
      }

      /* Modal: full screen di mobile */
      .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100% - 1rem);
      }

      .modal-dialog.modal-lg {
        max-width: calc(100% - 1rem);
      }

      /* Tombol aksi di tabel: pastikan bisa diklik */
      .btn-sm {
        min-width: 36px;
        min-height: 36px;
        padding: 0.4rem 0.6rem;
      }

      .btn-group .btn {
        min-height: 36px;
      }

      /* Footer: tengah */
      .footer .d-sm-flex {
        flex-direction: column;
        text-align: center;
      }
    }

    @media (max-width: 575.98px) {

      h2,
      .h2 {
        font-size: 1.2rem !important;
      }

      .card .card-body {
        padding: 1rem;
      }

      .modal-body {
        padding: 1rem;
      }

      .modal-header {
        padding: 1rem;
      }

      .modal-footer {
        padding: 1rem;
      }
    }
  </style>

</head>

<body>
  <div class="container-scroller">
    <!-- Navbar -->
    <nav class="navbar default-layout col-lg-12 col-12 p-0 d-flex align-items-center flex-row">
      <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
        <div class="me-2">
          <button class="navbar-toggler align-self-center border-0 bg-transparent shadow-none" type="button"
            id="sidebarToggle" style="padding:0.4rem;">
            <span class="mdi mdi-menu text-dark fs-3"></span>
          </button>
        </div>
        <div>
          <a class="navbar-brand brand-logo d-flex align-items-center text-decoration-none"
            href="{{ route('dashboard') }}">
            <img src="{{ asset('images/favicon-icon.png') }}" alt="logo" style="width: 28px; height: 28px;"
              class="me-2">
            <span style="font-size:0.9rem;">PASIEN JOURNEY</span>
          </a>
        </div>
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
        <ul class="navbar-nav ms-auto d-flex flex-row align-items-center gap-2">
          @auth
          <li class="nav-item d-none d-md-flex">
            <a class="nav-link fw-bold text-dark d-flex align-items-center text-decoration-none" href="#">
              <i class="mdi mdi-account-circle text-primary fs-4 me-1"></i>
              <span style="font-size:0.88rem;">{{ auth()->user()->name ?? 'Administrator RS' }}</span>
            </a>
          </li>
          <li class="nav-item">
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
              @csrf
              <button type="submit"
                class="btn btn-danger btn-sm text-white fw-bold btn-logout d-flex align-items-center"
                style="font-size:0.85rem; padding:0.45rem 0.85rem;">
                <i class="mdi mdi-power me-1"></i>
                <span>Keluar</span>
              </button>
            </form>
          </li>
          @else
          <li class="nav-item">
            <a href="{{ route('login') }}" class="btn btn-primary btn-sm text-white fw-bold d-flex align-items-center" style="font-size:0.85rem; padding:0.45rem 0.85rem;">
              <i class="mdi mdi-login me-1"></i>
              <span>Login</span>
            </a>
          </li>
          @endauth
        </ul>
        <button class="navbar-toggler d-lg-none border-0 bg-transparent shadow-none ms-2" type="button"
          id="sidebarToggleMobile" style="padding:0.4rem;">
          <span class="mdi mdi-menu text-dark fs-3"></span>
        </button>
      </div>
    </nav>

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Page Body Wrapper -->
    <div class="container-fluid page-body-wrapper">

      <!-- Sidebar -->
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <ul class="nav">
          @auth
          <li class="nav-item {{ request()->is('dashboard') || request()->is('/') ? 'active' : '' }}">
            <a class="nav-link text-decoration-none" href="{{ route('dashboard') }}">
              <i class="mdi mdi-view-dashboard"></i>
              <span class="menu-title">Dasbor Utama</span>
            </a>
          </li>

          <div class="category-heading">Manajemen Pasien</div>

          <li class="nav-item {{ request()->routeIs('equipments.*') ? 'active' : '' }}">
            <a class="nav-link text-decoration-none" href="{{ route('equipments.index') }}">
              <i class="mdi mdi-account-multiple"></i>
              <span class="menu-title">Manajemen Pasien</span>
            </a>
          </li>

          <li class="nav-item {{ request()->routeIs('maintenances.*') ? 'active' : '' }}">
            <a class="nav-link text-decoration-none" href="{{ route('maintenances.index') }}">
              <i class="mdi mdi-account-clock"></i>
              <span class="menu-title">Riwayat Pasien</span>
            </a>
          </li>

          @endauth

          {{-- Menu Manajemen Akun hanya untuk Admin --}}
          @if(auth()->check() && auth()->user()->isAdmin())
            <div class="category-heading">Pengaturan</div>

            <li class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
              <a class="nav-link text-decoration-none" href="{{ route('users.index') }}">
                <i class="mdi mdi-account-cog"></i>
                <span class="menu-title">Manajemen Akun</span>
              </a>
            </li>
          @endif
        </ul>
      </nav>

      <!-- Main Panel -->
      <div class="main-panel">
        <div class="content-wrapper">
          @yield('content_header')

          @yield('content')
        </div>

        <!-- Footer -->
        <footer class="footer">
          <div class="d-sm-flex justify-content-center justify-content-sm-between">
            <span class="text-center text-sm-left d-block d-sm-inline-block">Development SIMRS and TI</span>
            <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">&copy; Copyright 2026 | <b>Rumah
                Sakit Universitas Indonesia</b>. All rights reserved.</span>
          </div>
        </footer>
      </div>
      <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->

  <!-- Bootstrap 5 JS Bundle with Popper -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const toggleBtn = document.getElementById('sidebarToggle');
      const toggleBtnMobile = document.getElementById('sidebarToggleMobile');
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('sidebarOverlay');

      // Desktop toggler (collapse sidebar)
      if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
          if (window.innerWidth >= 992) {
            // Desktop: bisa ditambahkan toggle icon-only jika diperlukan
          } else {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
          }
        });
      }

      // Mobile toggler
      if (toggleBtnMobile) {
        toggleBtnMobile.addEventListener('click', function () {
          sidebar.classList.toggle('active');
          overlay.classList.toggle('active');
        });
      }

      // Klik overlay untuk menutup sidebar
      if (overlay) {
        overlay.addEventListener('click', function () {
          sidebar.classList.remove('active');
          overlay.classList.remove('active');
        });
      }

      // Tutup sidebar saat link diklik di mobile
      const sidebarLinks = sidebar ? sidebar.querySelectorAll('.nav-link') : [];
      sidebarLinks.forEach(function (link) {
        link.addEventListener('click', function () {
          if (window.innerWidth < 992) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
          }
        });
      });
    });
  </script>
</body>

</html>