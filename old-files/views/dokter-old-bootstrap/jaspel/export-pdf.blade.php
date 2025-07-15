<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Jaspel - {{ $periode }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            color: #666;
        }
        
        .info-section {
            margin-bottom: 20px;
        }
        
        .info-section table {
            width: 100%;
        }
        
        .info-section td {
            padding: 5px 0;
        }
        
        .summary-box {
            background-color: #f5f5f5;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        
        .summary-box h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .detail-table th,
        .detail-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        .detail-table th {
            background-color: #333;
            color: white;
            font-weight: bold;
        }
        
        .detail-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #e9e9e9;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .badge-umum {
            background-color: #007bff;
            color: white;
        }
        
        .badge-bpjs {
            background-color: #17a2b8;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN JASA PELAYANAN (JASPEL)</h1>
        <h2>{{ strtoupper($periode) }}</h2>
    </div>

    <div class="info-section">
        <table>
            <tr>
                <td width="150"><strong>Nama Dokter</strong></td>
                <td>: {{ $dokter->nama_lengkap }}</td>
            </tr>
            <tr>
                <td><strong>Spesialisasi</strong></td>
                <td>: {{ $dokter->spesialisasi ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Tanggal Cetak</strong></td>
                <td>: {{ now()->format('d F Y H:i:s') }}</td>
            </tr>
        </table>
    </div>

    <div class="summary-box">
        <h3>RINGKASAN</h3>
        <table width="100%">
            <tr>
                <td width="50%">Total Tindakan</td>
                <td class="text-right">: {{ $rekapData['total_tindakan'] }} tindakan</td>
            </tr>
            <tr>
                <td>Jaspel Pasien Umum</td>
                <td class="text-right">: Rp {{ number_format($rekapData['total_umum'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Jaspel Pasien BPJS</td>
                <td class="text-right">: Rp {{ number_format($rekapData['total_bpjs'], 0, ',', '.') }}</td>
            </tr>
            <tr style="border-top: 2px solid #333; font-weight: bold; font-size: 14px;">
                <td style="padding-top: 10px;">TOTAL JASPEL</td>
                <td class="text-right" style="padding-top: 10px;">: Rp {{ number_format($rekapData['total_umum'] + $rekapData['total_bpjs'], 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <h3>DETAIL TINDAKAN</h3>
    <table class="detail-table">
        <thead>
            <tr>
                <th width="15%">Tanggal</th>
                <th width="25%">Pasien</th>
                <th width="10%">Jenis</th>
                <th width="25%">Tindakan</th>
                <th width="10%">Shift</th>
                <th width="15%" class="text-right">Jasa Dokter</th>
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
                        {{ $tindakan->pasien->nama }}<br>
                        <small>{{ $tindakan->pasien->no_rekam_medis }}</small>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $jenisPasien === 'bpjs' ? 'badge-bpjs' : 'badge-umum' }}">
                            {{ strtoupper($jenisPasien) }}
                        </span>
                    </td>
                    <td>{{ $tindakan->jenisTindakan->nama }}</td>
                    <td class="text-center">{{ $tindakan->shift->name ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format($tindakan->jasa_dokter, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-right">TOTAL:</td>
                <td class="text-right">Rp {{ number_format($detailTindakan->sum('jasa_dokter'), 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Dokumen ini dicetak secara otomatis oleh sistem Dokterku</p>
        <p>&copy; {{ date('Y') }} Dokterku - Sistem Informasi Klinik</p>
    </div>
</body>
</html>