<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Models\DokterPresensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PresensiController extends Controller
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

        // Get presence history (last 7 days)
        $historiPresensi = DokterPresensi::byDokter($dokter->id)
            ->histori(7)
            ->get();

        return view('dokter.presensi.index', compact(
            'dokter',
            'presensiHariIni',
            'historiPresensi'
        ));
    }

    public function masuk(Request $request)
    {
        $user = Auth::user();
        $dokter = $user->dokter ?? Dokter::where('user_id', $user->id)->first();

        if (!$dokter) {
            return back()->with('error', 'User tidak terdaftar sebagai dokter');
        }

        // Check if already checked in today
        $presensiHariIni = DokterPresensi::hariIni()
            ->byDokter($dokter->id)
            ->first();

        if ($presensiHariIni && $presensiHariIni->jam_masuk) {
            return back()->with('error', 'Anda sudah melakukan presensi masuk hari ini');
        }

        // Create or update presensi
        DokterPresensi::updateOrCreate(
            [
                'dokter_id' => $dokter->id,
                'tanggal' => today()
            ],
            [
                'jam_masuk' => now()
            ]
        );

        return back()->with('success', 'Presensi masuk berhasil dicatat');
    }

    public function pulang(Request $request)
    {
        $user = Auth::user();
        $dokter = $user->dokter ?? Dokter::where('user_id', $user->id)->first();

        if (!$dokter) {
            return back()->with('error', 'User tidak terdaftar sebagai dokter');
        }

        // Get today's presence
        $presensiHariIni = DokterPresensi::hariIni()
            ->byDokter($dokter->id)
            ->first();

        if (!$presensiHariIni || !$presensiHariIni->jam_masuk) {
            return back()->with('error', 'Anda belum melakukan presensi masuk hari ini');
        }

        if ($presensiHariIni->jam_pulang) {
            return back()->with('error', 'Anda sudah melakukan presensi pulang hari ini');
        }

        // Update presensi pulang
        $presensiHariIni->update([
            'jam_pulang' => now()
        ]);

        return back()->with('success', 'Presensi pulang berhasil dicatat');
    }
}