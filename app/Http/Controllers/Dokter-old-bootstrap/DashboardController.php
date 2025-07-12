<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Models\DokterPresensi;
use App\Models\JaspelRekap;
use App\Models\Tindakan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
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

        // Get today's presence
        $presensiHariIni = DokterPresensi::hariIni()
            ->byDokter($dokter->id)
            ->first();

        // Get this month's jaspel
        $bulanIni = now()->month;
        $tahunIni = now()->year;
        
        $jaspelBulanIni = JaspelRekap::byDokter($dokter->id)
            ->byPeriode($bulanIni, $tahunIni)
            ->first();

        // If no rekap exists, calculate from tindakan
        if (!$jaspelBulanIni) {
            $rekapData = JaspelRekap::calculateFromTindakan($dokter->id, $bulanIni, $tahunIni);
            $totalJaspel = $rekapData['total_umum'] + $rekapData['total_bpjs'];
            $totalTindakan = $rekapData['total_tindakan'];
        } else {
            $totalJaspel = $jaspelBulanIni->total_jaspel;
            $totalTindakan = $jaspelBulanIni->total_tindakan;
        }

        // Get recent tindakan
        $recentTindakan = Tindakan::where('dokter_id', $dokter->id)
            ->where('status', 'selesai')
            ->where('status_validasi', 'disetujui')
            ->orderBy('tanggal_tindakan', 'desc')
            ->take(5)
            ->get();

        return view('dokter.dashboard', compact(
            'dokter',
            'presensiHariIni',
            'totalJaspel',
            'totalTindakan',
            'recentTindakan'
        ));
    }
}