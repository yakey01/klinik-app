@extends('layouts.dokter')

@section('content')
<div class="container-xxl">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col">
            <h1 class="h2 fw-bold">Jasa Pelayanan (JASPEL)</h1>
            <p class="text-muted">Rekap jasa pelayanan medis Anda</p>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <form id="filter-form" method="GET" action="{{ route('dokter.jaspel.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="month-selector" class="form-label">Bulan</label>
                            <select id="month-selector" name="bulan" class="form-select">
                                @foreach($months as $value => $label)
                                    <option value="{{ $value }}" {{ $bulan == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="year-selector" class="form-label">Tahun</label>
                            <select id="year-selector" name="tahun" class="form-select">
                                @foreach($years as $value => $label)
                                    <option value="{{ $value }}" {{ $tahun == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel me-2"></i>Filter
                            </button>
                            <a href="{{ route('dokter.jaspel.export', ['bulan' => $bulan, 'tahun' => $tahun, 'format' => 'pdf']) }}" 
                               class="btn btn-outline-secondary">
                                <i class="bi bi-file-pdf me-2"></i>Export PDF
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="card jaspel-summary-card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Tindakan</h6>
                    <h3 class="mb-0">{{ $jaspelRekap->total_tindakan }}</h3>
                    <small class="text-muted">Tindakan medis</small>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card jaspel-summary-card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Jaspel Pasien Umum</h6>
                    <p class="jaspel-amount mb-0">Rp {{ number_format($jaspelRekap->total_umum, 0, ',', '.') }}</p>
                    <small class="text-muted">
                        <i class="bi bi-person-badge text-primary"></i> Pasien Umum
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card jaspel-summary-card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Jaspel BPJS</h6>
                    <p class="jaspel-amount mb-0">Rp {{ number_format($jaspelRekap->total_bpjs, 0, ',', '.') }}</p>
                    <small class="text-muted">
                        <i class="bi bi-shield-check text-info"></i> Pasien BPJS
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card jaspel-summary-card h-100">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Status Pembayaran</h6>
                    <p class="mb-2">
                        @if($jaspelRekap->status_pembayaran === 'dibayar')
                            <span class="badge bg-success fs-6">
                                <i class="bi bi-check-circle me-1"></i>Dibayar
                            </span>
                        @elseif($jaspelRekap->status_pembayaran === 'pending')
                            <span class="badge bg-warning fs-6">
                                <i class="bi bi-clock me-1"></i>Pending
                            </span>
                        @else
                            <span class="badge bg-danger fs-6">
                                <i class="bi bi-x-circle me-1"></i>Ditolak
                            </span>
                        @endif
                    </p>
                    <small class="text-muted">{{ $jaspelRekap->periode }}</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Jaspel -->
    <div class="row mb-4">
        <div class="col">
            <div class="card bg-primary text-white">
                <div class="card-body text-center py-4">
                    <h5 class="mb-2">Total Jaspel {{ $jaspelRekap->periode }}</h5>
                    <h1 class="display-4 fw-bold mb-0">
                        Rp {{ number_format($jaspelRekap->total_jaspel, 0, ',', '.') }}
                    </h1>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Table -->
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Detail Tindakan</h5>
                    <div>
                        <span class="badge bg-primary me-2" data-bs-toggle="tooltip" title="Pasien Umum">
                            <i class="bi bi-person-badge"></i> Umum
                        </span>
                        <span class="badge bg-info" data-bs-toggle="tooltip" title="Pasien BPJS">
                            <i class="bi bi-shield-check"></i> BPJS
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    @if($detailTindakan->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Pasien</th>
                                        <th>Jenis</th>
                                        <th>Tindakan</th>
                                        <th>Shift</th>
                                        <th>Tarif</th>
                                        <th>Jasa Dokter</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($detailTindakan as $tindakan)
                                        @php
                                            $jenisPasien = $tindakan->pasien->jenis_pasien ?? 'umum';
                                        @endphp
                                        <tr>
                                            <td>{{ $tindakan->tanggal_tindakan->format('d/m/Y') }}</td>
                                            <td>
                                                {{ $tindakan->pasien->nama }}
                                                <br>
                                                <small class="text-muted">{{ $tindakan->pasien->no_rekam_medis }}</small>
                                            </td>
                                            <td>
                                                @if($jenisPasien === 'bpjs')
                                                    <span class="badge bg-info">BPJS</span>
                                                @else
                                                    <span class="badge bg-primary">Umum</span>
                                                @endif
                                            </td>
                                            <td>{{ $tindakan->jenisTindakan->nama }}</td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $tindakan->shift->name ?? '-' }}</span>
                                            </td>
                                            <td>Rp {{ number_format($tindakan->tarif, 0, ',', '.') }}</td>
                                            <td class="fw-bold text-success">
                                                Rp {{ number_format($tindakan->jasa_dokter, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <th colspan="6" class="text-end">Total:</th>
                                        <th class="text-success">
                                            Rp {{ number_format($detailTindakan->sum('jasa_dokter'), 0, ',', '.') }}
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <p class="text-muted text-center py-4">Tidak ada tindakan pada periode ini</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
</script>
@endpush