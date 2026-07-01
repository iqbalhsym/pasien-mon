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
      padding: 0 1rem;
      margin-bottom: 0.5rem;
    }

    .sidebar .nav .nav-item .nav-link {
      display: flex;
      align-items: center;
      padding: 0.8rem 1rem;
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

    /* Collapsed Sidebar for Desktop */
    @media (min-width: 992px) {
      body.sidebar-collapsed .sidebar {
        transform: translateX(-250px);
      }
      body.sidebar-collapsed .main-panel {
        width: 100% !important;
        margin-left: 0 !important;
      }
    }

    /* Custom Sidebar Sub-menu Styling to Prevent Cutoff */
    .sidebar .nav .nav-item .collapse .nav-item {
      padding: 0 !important;
      margin-bottom: 0.2rem;
    }
    .sidebar .nav .nav-item .collapse .nav-link {
      padding: 0.5rem 0.75rem !important;
      font-size: 0.88rem;
    }
    .sidebar .nav .nav-item .collapse .collapse .nav-link {
      padding: 0.4rem 0.75rem !important;
      font-size: 0.82rem;
    }

    /* Custom Scrollbar for Sidebar */
    .sidebar::-webkit-scrollbar {
      width: 6px;
    }
    .sidebar::-webkit-scrollbar-track {
      background: transparent;
    }
    .sidebar::-webkit-scrollbar-thumb {
      background: rgba(0, 0, 0, 0.15);
      border-radius: 10px;
    }
    .sidebar::-webkit-scrollbar-thumb:hover {
      background: rgba(0, 0, 0, 0.3);
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

          <div class="category-heading">Dashboard Mutu</div>
          <li class="nav-item {{ request()->routeIs('mutu.kepatuhan-visit') ? 'active' : '' }}">
            <a class="nav-link text-decoration-none" href="{{ route('mutu.kepatuhan-visit') }}">
              <i class="mdi mdi-chart-box-outline"></i>
              <span class="menu-title">Kepatuhan Visit DPJP</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('mutu.respon-konsul') ? 'active' : '' }}">
            <a class="nav-link text-decoration-none" href="{{ route('mutu.respon-konsul') }}">
              <i class="mdi mdi-message-text-clock-outline"></i>
              <span class="menu-title">Respon e-Konsul DPJP</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('mutu.distribusi-dpjp') ? 'active' : '' }}">
            <a class="nav-link text-decoration-none" href="{{ route('mutu.distribusi-dpjp') }}">
              <i class="mdi mdi-account-group-outline"></i>
              <span class="menu-title">Distribusi DPJP & Lantai</span>
            </a>
          </li>
          <li class="nav-item {{ request()->routeIs('mutu.jadwal-ners') ? 'active' : '' }}">
            <a class="nav-link text-decoration-none" href="{{ route('mutu.jadwal-ners') }}">
              <i class="mdi mdi-calendar-clock"></i>
              <span class="menu-title">Laporan Shift Ners</span>
            </a>
          </li>

          <div class="category-heading">Manajemen Pasien</div>

          <li class="nav-item {{ request()->routeIs('beds.*') ? 'active' : '' }}">
            <a class="nav-link text-decoration-none" href="{{ route('beds.index') }}">
              <i class="mdi mdi-bed-outline"></i>
              <span class="menu-title">Monitoring Bed & Kamar</span>
            </a>
          </li>

          <li class="nav-item {{ request()->routeIs('maintenances.index') || request()->routeIs('maintenances.patient_detail') || request()->routeIs('maintenances.history') ? 'active' : '' }}">
            <a class="nav-link text-decoration-none" href="{{ route('maintenances.index') }}">
              <i class="mdi mdi-account-clock"></i>
              <span class="menu-title">Riwayat Pasien</span>
            </a>
          </li>

          <li class="nav-item {{ request()->routeIs('maintenances.pulang') ? 'active' : '' }}">
            <a class="nav-link text-decoration-none" href="{{ route('maintenances.pulang') }}">
              <i class="mdi mdi-account-off"></i>
              <span class="menu-title">Pasien Sudah Pulang</span>
            </a>
          </li>

          @php
              $currentRoute = request()->routeIs('beds.*') ? 'beds.index' : 'maintenances.index';
              $queryParam = request()->routeIs('beds.*') ? 'floor' : 'lantai';
          @endphp
          <div class="category-heading">ZONA RAWAT INAP</div>
          
          <!-- All Floors Option -->
          <li class="nav-item mb-1">
            <a class="nav-link text-decoration-none d-flex align-items-center py-2 px-3 {{ !request($queryParam) ? 'active' : '' }}" 
               href="{{ route($currentRoute, request()->except([$queryParam, 'wing', 'room'])) }}"
               style="border-radius: 8px; font-weight: 600; font-size: 0.92rem; color: {{ !request($queryParam) ? '#ffffff' : '#6c7383' }}; background: {{ !request($queryParam) ? '#1F3BB3' : 'transparent' }};">
              <i class="mdi mdi-layers-outline me-2 fs-5" style="color: {{ !request($queryParam) ? '#ffffff' : '#b9b9b9' }};"></i>
              <span>Semua Lantai</span>
            </a>
          </li>

          @foreach($globalFloors as $fl)
            @php
                $flName = $fl->name;
                $displayFl = is_numeric($flName) ? 'Lantai ' . $flName : $flName;
                
                // Format floor parameter for query string compatibility
                $paramFloor = $flName;
                if (preg_match('/Lantai\s+(\d+)/i', $flName, $matches)) {
                    $paramFloor = $matches[1];
                }
                
                $isFloorActive = request($queryParam) == $paramFloor;
                $floorCollapseId = 'floorCollapse_' . $fl->id;
            @endphp
            <li class="nav-item mb-1">
              <!-- Level 1: Floor -->
              <a class="nav-link text-decoration-none d-flex justify-content-between align-items-center py-2 px-3 {{ $isFloorActive ? 'active' : '' }}" 
                 data-bs-toggle="collapse" 
                 href="#{{ $floorCollapseId }}" 
                 role="button" 
                 aria-expanded="{{ $isFloorActive ? 'true' : 'false' }}"
                 style="border-radius: 8px; font-weight: 600; font-size: 0.92rem; color: {{ $isFloorActive ? '#ffffff' : '#6c7383' }}; background: {{ $isFloorActive ? '#1F3BB3' : 'transparent' }};">
                <div class="d-flex align-items-center">
                  <i class="mdi mdi-office-building me-2 fs-5" style="color: {{ $isFloorActive ? '#ffffff' : '#b9b9b9' }};"></i>
                  <span>{{ $displayFl }}</span>
                </div>
                <i class="mdi mdi-chevron-down toggle-icon ms-auto" style="transition: transform 0.2s; transform: {{ $isFloorActive ? 'rotate(180deg)' : 'rotate(0deg)' }};"></i>
              </a>

              <!-- Level 2: Wings Collapsible -->
              <div class="collapse {{ $isFloorActive ? 'show' : '' }}" id="{{ $floorCollapseId }}">
                <ul class="nav flex-column ms-2 mt-1" style="padding: 0; list-style: none;">
                  
                  <!-- View all rooms in this floor link -->
                  <li class="nav-item mb-1">
                    <a class="nav-link text-decoration-none py-1.5 px-3 d-flex align-items-center" 
                       href="{{ route($currentRoute, array_merge(request()->except(['wing', 'room']), [$queryParam => $paramFloor])) }}"
                       style="font-size: 0.88rem; font-weight: 500; color: {{ ($isFloorActive && !request('wing')) ? '#1F3BB3' : '#6c7383' }};">
                      <i class="mdi mdi-layers-triple-outline me-2" style="font-size: 1rem;"></i>
                      <span>Semua Kamar di Lantai ini</span>
                    </a>
                  </li>

                  @foreach($fl->wings as $wing)
                    @php
                        $isWingActive = $isFloorActive && request('wing') == $wing->name;
                        $wingCollapseId = 'wingCollapse_' . $wing->id;
                    @endphp
                    <li class="nav-item mb-1">
                      <!-- Wing Toggler -->
                      <a class="nav-link text-decoration-none d-flex justify-content-between align-items-center py-1.5 px-3" 
                         data-bs-toggle="collapse" 
                         href="#{{ $wingCollapseId }}" 
                         role="button" 
                         aria-expanded="{{ $isWingActive ? 'true' : 'false' }}"
                         style="font-size: 0.88rem; font-weight: 500; color: {{ $isWingActive ? '#1F3BB3' : '#6c7383' }};">
                        <div class="d-flex align-items-center">
                          <i class="mdi mdi-layers me-2" style="font-size: 1rem;"></i>
                          <span>{{ $wing->name }}</span>
                        </div>
                        <i class="mdi mdi-chevron-down toggle-icon ms-auto" style="font-size: 0.8rem; transition: transform 0.2s; transform: {{ $isWingActive ? 'rotate(180deg)' : 'rotate(0deg)' }};"></i>
                      </a>

                      <!-- Level 3: Rooms Collapsible -->
                      <div class="collapse {{ $isWingActive ? 'show' : '' }}" id="{{ $wingCollapseId }}">
                        <ul class="nav flex-column ms-2 mt-1" style="padding: 0; list-style: none;">
                          
                          <!-- View all rooms in this wing link -->
                          <li class="nav-item mb-1">
                            <a class="nav-link text-decoration-none py-1.5 px-3 d-flex align-items-center" 
                               href="{{ route($currentRoute, array_merge(request()->except(['room']), [$queryParam => $paramFloor, 'wing' => $wing->name])) }}"
                               style="font-size: 0.84rem; font-weight: 500; color: {{ ($isWingActive && !request('room')) ? '#1F3BB3' : '#6c7383' }};">
                              <i class="mdi mdi-circle-double me-2" style="font-size: 0.9rem;"></i>
                              <span>Semua di {{ $wing->name }}</span>
                            </a>
                          </li>

                          @foreach($wing->rooms as $room)
                            @php
                                $isRoomActive = $isWingActive && request('room') == $room->name;
                                $patientCount = $room->occupied_beds_count ?? 0;
                                
                                // Style badge matching reference image (Pill layout: border red/green, custom inner bg/text)
                                $badgeStyle = $patientCount > 0 
                                    ? 'border: 2px solid #198754; background-color: #e8f5e9; color: #198754;' 
                                    : 'border: 2px solid #dc3545; background-color: #ffffff; color: #dc3545;';
                            @endphp
                            <li class="nav-item mb-1">
                              <a class="nav-link text-decoration-none d-flex justify-content-between align-items-center py-1.5 px-3" 
                                 href="{{ route($currentRoute, [$queryParam => $paramFloor, 'wing' => $wing->name, 'room' => $room->name]) }}"
                                 style="font-size: 0.84rem; font-weight: 500; color: {{ $isRoomActive ? '#1F3BB3' : '#6c7383' }}; background-color: {{ $isRoomActive ? 'rgba(31,59,179,0.05)' : 'transparent' }}; border-radius: 4px;">
                                <div class="d-flex align-items-center text-truncate" style="max-width: 170px;">
                                  <i class="mdi mdi-door-open me-2" style="font-size: 0.9rem;"></i>
                                  <span class="text-truncate" title="{{ $room->name }}">{{ $room->name }}</span>
                                </div>
                                <span class="badge rounded-pill fw-bold" style="{{ $badgeStyle }} font-size: 0.75rem; padding: 2px 7px; min-width: 28px; height: 20px; display: inline-flex; align-items: center; justify-content: center;">
                                  {{ $patientCount }}
                                </span>
                              </a>
                            </li>
                          @endforeach
                        </ul>
                      </div>
                    </li>
                  @endforeach
                </ul>
              </div>
            </li>
          @endforeach

          <script>
            // Simple JS helper to rotate chevrons when Bootstrap collapse toggles
            document.addEventListener('DOMContentLoaded', function () {
              const accordion = document.getElementById('sidebarRoomsAccordion');
              if (accordion) {
                accordion.addEventListener('show.bs.collapse', function (e) {
                  const toggler = document.querySelector(`[href="#${e.target.id}"]`);
                  if (toggler) {
                    const chevron = toggler.querySelector('.toggle-icon');
                    if (chevron) {
                      chevron.style.transform = 'rotate(180deg)';
                    }
                  }
                });
                accordion.addEventListener('hide.bs.collapse', function (e) {
                  const toggler = document.querySelector(`[href="#${e.target.id}"]`);
                  if (toggler) {
                    const chevron = toggler.querySelector('.toggle-icon');
                    if (chevron) {
                      chevron.style.transform = 'rotate(0deg)';
                    }
                  }
                });
              }
            });
          </script>

          @endauth

          {{-- Menu Pengaturan --}}
          @if(auth()->check() && (auth()->user()->isAdmin() || auth()->user()->role === 'user'))
            <div class="category-heading">Pengaturan</div>

            @if(auth()->user()->isAdmin())
              <li class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <a class="nav-link text-decoration-none" href="{{ route('users.index') }}">
                  <i class="mdi mdi-account-cog"></i>
                  <span class="menu-title">Manajemen Akun</span>
                </a>
              </li>
            @endif

            <li class="nav-item {{ request()->routeIs('nurses.*') ? 'active' : '' }}">
              <a class="nav-link text-decoration-none" href="{{ route('nurses.index') }}">
                <i class="mdi mdi-account-group"></i>
                <span class="menu-title">Manajemen Data Ners</span>
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
            document.body.classList.toggle('sidebar-collapsed');
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

      // Auto-Sync & Navigation Sync trigger (Runs silently in the background)
      @auth
      setTimeout(function() {
        fetch("{{ route('beds.sync') }}", {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        }).then(response => {
          if (response.ok) {
            return response.json();
          }
        }).then(data => {
          // If sync finished and data actually updated (not skipped), and we are on a list view, we can reload to show latest data
          if (data && data.success && data.message && !data.message.includes('dilewati')) {
            const isIndexPage = window.location.pathname.includes('/beds') || 
                               window.location.pathname.includes('/pasien') || 
                               window.location.pathname.includes('/rekam-medis');
            const isModalOpen = document.querySelector('.modal.show') !== null;
            const isUserTyping = document.activeElement && 
                                (document.activeElement.tagName === 'INPUT' || 
                                 document.activeElement.tagName === 'TEXTAREA');

            if (isIndexPage && !isModalOpen && !isUserTyping) {
              window.location.reload();
            }
          }
        }).catch(err => console.error('Silent sync error:', err));
      }, 1500);

      // Interval auto-sync every 1 minute
      setInterval(function() {
        fetch("{{ route('beds.sync') }}", {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        }).then(response => {
          if (response.ok) {
            return response.json();
          }
        }).then(data => {
          if (data && data.success && data.message && !data.message.includes('dilewati')) {
            const isIndexPage = window.location.pathname.includes('/beds') || 
                               window.location.pathname.includes('/pasien') || 
                               window.location.pathname.includes('/rekam-medis');
            const isModalOpen = document.querySelector('.modal.show') !== null;
            const isUserTyping = document.activeElement && 
                                (document.activeElement.tagName === 'INPUT' || 
                                 document.activeElement.tagName === 'TEXTAREA');

            if (isIndexPage && !isModalOpen && !isUserTyping) {
              window.location.reload();
            }
          }
        }).catch(err => console.error('Interval sync error:', err));
      }, 60000);
      @endauth
    });
  </script>
</body>

</html>