<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Dokterku') }} - Dashboard Dokter</title>

    <!-- Bootstrap 5.3 CSS (Dark Mode Compatible) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    
    <!-- AdminLTE 3 Dark Theme -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    
    <!-- Custom Dokter CSS (Isolated from Tailwind) -->
    @vite(['resources/css/dokter.css', 'resources/js/dokter.js'])
    
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini dark-mode">
<div class="wrapper">
  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-dark">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas bi-bars"></i></a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Real-time Clock -->
      <li class="nav-item">
        <span class="navbar-text">
          <i class="bi bi-clock me-2"></i>
          <span id="realtime-clock">00:00:00</span>
        </span>
      </li>
      
      <!-- User Dropdown -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="bi bi-person-circle"></i>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <span class="dropdown-item dropdown-header">{{ Auth::user()->name }}</span>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <i class="bi bi-person-gear mr-2"></i> Profile
          </a>
          <div class="dropdown-divider"></div>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dropdown-item">
              <i class="bi bi-box-arrow-right mr-2"></i> Logout
            </button>
          </form>
        </div>
      </li>
    </ul>
  </nav>

  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('dokter.dashboard') }}" class="brand-link">
      <i class="bi bi-hospital brand-image img-circle elevation-3" style="font-size: 2rem; opacity: .8"></i>
      <span class="brand-text font-weight-light">Dokterku</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <i class="bi bi-person-circle" style="font-size: 2rem; color: #fff;"></i>
        </div>
        <div class="info">
          <a href="#" class="d-block">{{ Auth::user()->name }}</a>
          <small class="text-success">
            <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i> Online
          </small>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Dashboard -->
          <li class="nav-item">
            <a href="{{ route('dokter.dashboard') }}" class="nav-link {{ request()->routeIs('dokter.dashboard') ? 'active' : '' }}">
              <i class="nav-icon bi bi-speedometer2"></i>
              <p>Dashboard</p>
            </a>
          </li>
          
          <!-- Presensi -->
          <li class="nav-item">
            <a href="{{ route('dokter.presensi.index') }}" class="nav-link {{ request()->routeIs('dokter.presensi*') ? 'active' : '' }}">
              <i class="nav-icon bi bi-clock-history"></i>
              <p>Presensi</p>
            </a>
          </li>
          
          <!-- Jaspel -->
          <li class="nav-item">
            <a href="{{ route('dokter.jaspel.index') }}" class="nav-link {{ request()->routeIs('dokter.jaspel*') ? 'active' : '' }}">
              <i class="nav-icon bi bi-cash-stack"></i>
              <p>Jaspel</p>
            </a>
          </li>
          
          <!-- Divider -->
          <li class="nav-header">PENGATURAN</li>
          
          <!-- Profile -->
          <li class="nav-item">
            <a href="#" class="nav-link" onclick="showProfileSettings()">
              <i class="nav-icon bi bi-person-gear"></i>
              <p>Profil Saya</p>
            </a>
          </li>
          
          <!-- Settings -->
          <li class="nav-item">
            <a href="#" class="nav-link" onclick="showSettings()">
              <i class="nav-icon bi bi-gear"></i>
              <p>Pengaturan</p>
            </a>
          </li>
          
          <!-- Help -->
          <li class="nav-item">
            <a href="#" class="nav-link" onclick="showHelp()">
              <i class="nav-icon bi bi-question-circle"></i>
              <p>Bantuan</p>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </aside>

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header">
      <div class="container-fluid">
        @if(session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif

        @if(session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        @endif
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        @yield('content')
      </div>
    </section>
  </div>

  <!-- Main Footer -->
  <footer class="main-footer">
    <strong>Copyright &copy; {{ date('Y') }} <a href="#">Dokterku</a>.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 1.0.0
    </div>
  </footer>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="me-auto">Notifikasi</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>

<script>
// Placeholder functions for menu items
function showProfileSettings() {
    alert('Fitur Profil akan segera hadir!');
}

function showSettings() {
    alert('Fitur Pengaturan akan segera hadir!');
}

function showHelp() {
    alert('Bantuan:\n\n1. Dashboard: Lihat ringkasan aktivitas\n2. Presensi: Catat kehadiran harian\n3. Jaspel: Lihat rekap jasa pelayanan\n\nHubungi admin untuk bantuan lebih lanjut.');
}
</script>

@stack('scripts')
</body>
</html>