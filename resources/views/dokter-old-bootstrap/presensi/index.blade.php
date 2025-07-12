@extends('layouts.dokter')

@section('content')
<div class="container-xxl">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col">
            <h1 class="h2 fw-bold">Presensi Dokter</h1>
            <p class="text-muted">Kelola presensi harian Anda</p>
        </div>
    </div>

    <!-- Clock and Presensi Buttons -->
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body text-center presensi-card">
                    <h5 class="card-title mb-4">Jam Digital</h5>
                    <div id="realtime-clock" class="clock-display mb-4">00:00:00</div>
                    <p class="text-muted">{{ now()->format('l, d F Y') }}</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body text-center presensi-card">
                    <h5 class="card-title mb-4">Presensi Masuk</h5>
                    @if(!$presensiHariIni || !$presensiHariIni->jam_masuk)
                        <form id="presensi-masuk-form" action="{{ route('dokter.presensi.masuk') }}" method="POST">
                            @csrf
                            <button type="button" id="presensi-masuk-btn" class="btn btn-success presensi-button">
                                <i class="bi bi-box-arrow-in-right"></i>
                                <span>Masuk</span>
                            </button>
                        </form>
                    @else
                        <div class="presensi-button btn btn-secondary disabled">
                            <i class="bi bi-check-circle"></i>
                            <span>Sudah Masuk</span>
                            <small class="d-block mt-2">{{ \Carbon\Carbon::parse($presensiHariIni->jam_masuk)->format('H:i:s') }}</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body text-center presensi-card">
                    <h5 class="card-title mb-4">Presensi Pulang</h5>
                    @if($presensiHariIni && $presensiHariIni->jam_masuk && !$presensiHariIni->jam_pulang)
                        <form id="presensi-pulang-form" action="{{ route('dokter.presensi.pulang') }}" method="POST">
                            @csrf
                            <button type="button" id="presensi-pulang-btn" class="btn btn-danger presensi-button">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Pulang</span>
                            </button>
                        </form>
                    @elseif($presensiHariIni && $presensiHariIni->jam_pulang)
                        <div class="presensi-button btn btn-secondary disabled">
                            <i class="bi bi-check-circle"></i>
                            <span>Sudah Pulang</span>
                            <small class="d-block mt-2">{{ \Carbon\Carbon::parse($presensiHariIni->jam_pulang)->format('H:i:s') }}</small>
                        </div>
                    @else
                        <div class="presensi-button btn btn-secondary disabled">
                            <i class="bi bi-x-circle"></i>
                            <span>Belum Masuk</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Status Summary -->
    @if($presensiHariIni)
        <div class="row mb-4">
            <div class="col">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Ringkasan Hari Ini</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <h6 class="text-muted">Status</h6>
                                <p class="fs-5">
                                    <span class="badge bg-{{ $presensiHariIni->status === 'Selesai' ? 'success' : 'primary' }} fs-6">
                                        {{ $presensiHariIni->status }}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-muted">Jam Masuk</h6>
                                <p class="fs-5">{{ $presensiHariIni->jam_masuk ? \Carbon\Carbon::parse($presensiHariIni->jam_masuk)->format('H:i:s') : '-' }}</p>
                            </div>
                            <div class="col-md-4">
                                <h6 class="text-muted">Jam Pulang</h6>
                                <p class="fs-5">{{ $presensiHariIni->jam_pulang ? \Carbon\Carbon::parse($presensiHariIni->jam_pulang)->format('H:i:s') : '-' }}</p>
                            </div>
                        </div>
                        @if($presensiHariIni->durasi)
                            <hr>
                            <div class="text-center">
                                <h6 class="text-muted">Total Durasi Kerja</h6>
                                <p class="fs-4 fw-bold text-primary mb-0">{{ $presensiHariIni->durasi }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- History Table -->
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Histori Presensi (7 Hari Terakhir)</h5>
                </div>
                <div class="card-body">
                    @if($historiPresensi->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Hari</th>
                                        <th>Jam Masuk</th>
                                        <th>Jam Pulang</th>
                                        <th>Durasi</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($historiPresensi as $presensi)
                                        <tr>
                                            <td>{{ $presensi->tanggal->format('d/m/Y') }}</td>
                                            <td>{{ $presensi->tanggal->translatedFormat('l') }}</td>
                                            <td>
                                                @if($presensi->jam_masuk)
                                                    <span class="text-success">
                                                        {{ \Carbon\Carbon::parse($presensi->jam_masuk)->format('H:i:s') }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($presensi->jam_pulang)
                                                    <span class="text-danger">
                                                        {{ \Carbon\Carbon::parse($presensi->jam_pulang)->format('H:i:s') }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($presensi->durasi)
                                                    <span class="fw-bold">{{ $presensi->durasi }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $presensi->status === 'Selesai' ? 'success' : ($presensi->status === 'Sedang Bertugas' ? 'primary' : 'secondary') }}">
                                                    {{ $presensi->status }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center py-4">Belum ada histori presensi</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection