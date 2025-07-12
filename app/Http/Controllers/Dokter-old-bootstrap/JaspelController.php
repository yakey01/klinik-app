<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Models\JaspelRekap;
use App\Models\Tindakan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class JaspelController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $dokter = $user->dokter;

        if (!$dokter) {
            // Create a basic dokter record if it doesn't exist
            $dokter = Dokter::create([
                'user_id' => $user->id,
                'nama_lengkap' => $user->name,
                'email' => $user->email,
                'jabatan' => 'dokter_umum',
                'aktif' => true,
                'input_by' => $user->id
            ]);
        }

        // Get filter parameters
        $bulan = $request->input('bulan', now()->month);
        $tahun = $request->input('tahun', now()->year);

        // Get jaspel rekap
        $jaspelRekap = JaspelRekap::byDokter($dokter->id)
            ->byPeriode($bulan, $tahun)
            ->first();

        // If no rekap exists, calculate from tindakan
        if (!$jaspelRekap) {
            $rekapData = JaspelRekap::calculateFromTindakan($dokter->id, $bulan, $tahun);
            
            // Create temporary object for display
            $jaspelRekap = new JaspelRekap([
                'dokter_id' => $dokter->id,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'total_umum' => $rekapData['total_umum'],
                'total_bpjs' => $rekapData['total_bpjs'],
                'total_tindakan' => $rekapData['total_tindakan'],
                'status_pembayaran' => 'pending'
            ]);
        }

        // Get detailed tindakan for the period
        $startDate = \Carbon\Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $detailTindakan = Tindakan::where('dokter_id', $dokter->id)
            ->whereBetween('tanggal_tindakan', [$startDate, $endDate])
            ->where('status', 'selesai')
            ->where('status_validasi', 'disetujui')
            ->with(['pasien', 'jenisTindakan', 'shift'])
            ->orderBy('tanggal_tindakan', 'desc')
            ->get();

        // Generate months and years for dropdown
        $months = collect(range(1, 12))->mapWithKeys(function ($month) {
            return [$month => \Carbon\Carbon::create()->month($month)->format('F')];
        });

        $years = collect(range(now()->year - 2, now()->year))->mapWithKeys(function ($year) {
            return [$year => $year];
        });

        return view('dokter.jaspel.index', compact(
            'dokter',
            'jaspelRekap',
            'detailTindakan',
            'bulan',
            'tahun',
            'months',
            'years'
        ));
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        $dokter = $user->dokter ?? Dokter::where('user_id', $user->id)->first();

        if (!$dokter) {
            abort(403, 'User tidak terdaftar sebagai dokter');
        }

        $bulan = $request->input('bulan', now()->month);
        $tahun = $request->input('tahun', now()->year);
        $format = $request->input('format', 'pdf');

        // Get data
        $startDate = \Carbon\Carbon::create($tahun, $bulan, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $detailTindakan = Tindakan::where('dokter_id', $dokter->id)
            ->whereBetween('tanggal_tindakan', [$startDate, $endDate])
            ->where('status', 'selesai')
            ->where('status_validasi', 'disetujui')
            ->with(['pasien', 'jenisTindakan', 'shift'])
            ->orderBy('tanggal_tindakan', 'desc')
            ->get();

        $rekapData = JaspelRekap::calculateFromTindakan($dokter->id, $bulan, $tahun);

        if ($format === 'pdf') {
            $pdf = PDF::loadView('dokter.jaspel.export-pdf', [
                'dokter' => $dokter,
                'detailTindakan' => $detailTindakan,
                'rekapData' => $rekapData,
                'bulan' => $bulan,
                'tahun' => $tahun,
                'periode' => \Carbon\Carbon::create()->month($bulan)->format('F') . ' ' . $tahun
            ]);

            return $pdf->download('jaspel-' . $bulan . '-' . $tahun . '.pdf');
        }

        // Excel export would go here if needed
        return back()->with('error', 'Format export tidak didukung');
    }
}