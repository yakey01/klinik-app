@extends('layouts.dokter')

@section('page-title', 'Dashboard Dokter')

@section('content')
<!-- Content Header (Page header) -->
<div class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Dashboard Dokter</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item active">Dashboard</li>
        </ol>
      </div>
    </div>
  </div>
</div>

<!-- Info boxes -->
<div class="row">
  <div class="col-12 col-sm-6 col-md-3">
    <div class="info-box">
      <span class="info-box-icon bg-info elevation-1"><i class="bi bi-clock-history"></i></span>
      <div class="info-box-content">
        <span class="info-box-text">Status Presensi</span>
        <span class="info-box-number">
          @if($presensiHariIni)
            <span class="badge badge-success">{{ $presensiHariIni->status }}</span>
          @else
            <span class="badge badge-danger">Belum Hadir</span>
          @endif
        </span>
      </div>
    </div>
  </div>
  
  <div class="col-12 col-sm-6 col-md-3">
    <div class="info-box mb-3">
      <span class="info-box-icon bg-danger elevation-1"><i class="bi bi-heart-pulse"></i></span>
      <div class="info-box-content">
        <span class="info-box-text">Tindakan Bulan Ini</span>
        <span class="info-box-number">{{ $totalTindakan }}</span>
      </div>
    </div>
  </div>
  
  <div class="clearfix hidden-md-up"></div>
  
  <div class="col-12 col-sm-6 col-md-3">
    <div class="info-box mb-3">
      <span class="info-box-icon bg-success elevation-1"><i class="bi bi-cash-stack"></i></span>
      <div class="info-box-content">
        <span class="info-box-text">Jaspel Bulan Ini</span>
        <span class="info-box-number">Rp {{ number_format($totalJaspel, 0, ',', '.') }}</span>
      </div>
    </div>
  </div>
  
  <div class="col-12 col-sm-6 col-md-3">
    <div class="info-box mb-3">
      <span class="info-box-icon bg-warning elevation-1"><i class="bi bi-tools"></i></span>
      <div class="info-box-content">
        <span class="info-box-text">Quick Actions</span>
        <div class="mt-2">
          <a href="{{ route('dokter.presensi.index') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-clock"></i> Presensi
          </a>
          <a href="{{ route('dokter.jaspel.index') }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-cash"></i> Jaspel
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main row -->
<div class="row">
  <!-- Left col -->
  <section class="col-lg-8 connectedSortable">
    <!-- Tindakan Terbaru -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">
          <i class="bi bi-clipboard-heart mr-1"></i>
          Tindakan Terbaru
        </h3>
      </div>
      <div class="card-body">
        @if($recentTindakan->count() > 0)
          <div class="table-responsive">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Tanggal</th>
                  <th>Pasien</th>
                  <th>Tindakan</th>
                  <th>Jasa Dokter</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($recentTindakan as $tindakan)
                  <tr>
                    <td>{{ $tindakan->tanggal_tindakan->format('d/m/Y') }}</td>
                    <td>{{ $tindakan->pasien->nama }}</td>
                    <td>{{ $tindakan->jenisTindakan->nama }}</td>
                    <td>Rp {{ number_format($tindakan->jasa_dokter, 0, ',', '.') }}</td>
                    <td>
                      <span class="badge badge-success">Disetujui</span>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <p class="text-muted text-center py-4">Belum ada tindakan terbaru</p>
        @endif
      </div>
    </div>
  </section>

  <!-- Right col -->
  <section class="col-lg-4 connectedSortable">
    <!-- Informasi Hari Ini -->
    <div class="card bg-gradient-info">
      <div class="card-header border-0">
        <h3 class="card-title">
          <i class="bi bi-calendar-day mr-1"></i>
          Informasi Hari Ini
        </h3>
      </div>
      <div class="card-body">
        <div class="text-center">
          <h6 class="text-white-50">Jam Digital</h6>
          <h2 id="realtime-clock" class="text-white font-weight-bold">00:00:00</h2>
          <p class="text-white-50">{{ now()->format('l, d F Y') }}</p>
        </div>
        
        @if($presensiHariIni)
          <hr class="border-light">
          <div class="row">
            <div class="col-6">
              <div class="text-center">
                <h6 class="text-white-50">Jam Masuk</h6>
                <p class="text-white font-weight-bold">{{ $presensiHariIni->jam_masuk ? \Carbon\Carbon::parse($presensiHariIni->jam_masuk)->format('H:i:s') : '-' }}</p>
              </div>
            </div>
            <div class="col-6">
              <div class="text-center">
                <h6 class="text-white-50">Jam Pulang</h6>
                <p class="text-white font-weight-bold">{{ $presensiHariIni->jam_pulang ? \Carbon\Carbon::parse($presensiHariIni->jam_pulang)->format('H:i:s') : '-' }}</p>
              </div>
            </div>
          </div>
          
          @if($presensiHariIni->durasi)
            <div class="text-center">
              <h6 class="text-white-50">Durasi Kerja</h6>
              <h4 class="text-white font-weight-bold">{{ $presensiHariIni->durasi }}</h4>
            </div>
          @endif
        @else
          <hr class="border-light">
          <div class="text-center">
            <p class="text-white-50">Anda belum melakukan presensi hari ini</p>
            <a href="{{ route('dokter.presensi.index') }}" class="btn btn-light">
              <i class="bi bi-clock me-2"></i>Lakukan Presensi
            </a>
          </div>
        @endif
      </div>
    </div>
  </section>
</div>
@endsection