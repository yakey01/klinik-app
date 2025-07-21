<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Jaspel;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use App\Models\UangDuduk;
use Illuminate\Http\Request;

class ValidasiController extends Controller
{
    public function getPendingValidations(Request $request)
    {
        $type = $request->get('type', 'all');
        
        $data = [];
        
        if ($type === 'all' || $type === 'pendapatan') {
            $data['pendapatan'] = Pendapatan::pending()
                ->with(['inputBy', 'tindakan'])
                ->latest()
                ->take(10)
                ->get();
        }
        
        if ($type === 'all' || $type === 'pengeluaran') {
            $data['pengeluaran'] = Pengeluaran::pending()
                ->with(['inputBy'])
                ->latest()
                ->take(10)
                ->get();
        }
        
        if ($type === 'all' || $type === 'jaspel') {
            $data['jaspel'] = Jaspel::pending()
                ->with(['user', 'tindakan', 'inputBy'])
                ->latest()
                ->take(10)
                ->get();
        }
        
        if ($type === 'all' || $type === 'uang_duduk') {
            $data['uang_duduk'] = UangDuduk::pending()
                ->with(['user', 'inputBy'])
                ->latest()
                ->take(10)
                ->get();
        }
        
        return response()->json($data);
    }

    public function approvePendapatan(Request $request, Pendapatan $pendapatan)
    {
        $validated = $request->validate([
            'catatan_validasi' => 'nullable|string',
        ]);

        $pendapatan->update([
            'status_validasi' => 'disetujui',
            'validasi_by' => auth()->id(),
            'validasi_at' => now(),
            'catatan_validasi' => $validated['catatan_validasi'] ?? null,
        ]);

        return response()->json(['message' => 'Pendapatan approved successfully']);
    }

    public function rejectPendapatan(Request $request, Pendapatan $pendapatan)
    {
        $validated = $request->validate([
            'catatan_validasi' => 'required|string',
        ]);

        $pendapatan->update([
            'status_validasi' => 'ditolak',
            'validasi_by' => auth()->id(),
            'validasi_at' => now(),
            'catatan_validasi' => $validated['catatan_validasi'],
        ]);

        return response()->json(['message' => 'Pendapatan rejected successfully']);
    }

    public function approvePengeluaran(Request $request, Pengeluaran $pengeluaran)
    {
        $validated = $request->validate([
            'catatan_validasi' => 'nullable|string',
        ]);

        $pengeluaran->update([
            'status_validasi' => 'disetujui',
            'validasi_by' => auth()->id(),
            'validasi_at' => now(),
            'catatan_validasi' => $validated['catatan_validasi'] ?? null,
        ]);

        return response()->json(['message' => 'Pengeluaran approved successfully']);
    }

    public function rejectPengeluaran(Request $request, Pengeluaran $pengeluaran)
    {
        $validated = $request->validate([
            'catatan_validasi' => 'required|string',
        ]);

        $pengeluaran->update([
            'status_validasi' => 'ditolak',
            'validasi_by' => auth()->id(),
            'validasi_at' => now(),
            'catatan_validasi' => $validated['catatan_validasi'],
        ]);

        return response()->json(['message' => 'Pengeluaran rejected successfully']);
    }

    public function approveJaspel(Request $request, Jaspel $jaspel)
    {
        $validated = $request->validate([
            'catatan_validasi' => 'nullable|string',
        ]);

        $jaspel->update([
            'status_validasi' => 'disetujui',
            'validasi_by' => auth()->id(),
            'validasi_at' => now(),
            'catatan_validasi' => $validated['catatan_validasi'] ?? null,
        ]);

        return response()->json(['message' => 'Jaspel approved successfully']);
    }

    public function rejectJaspel(Request $request, Jaspel $jaspel)
    {
        $validated = $request->validate([
            'catatan_validasi' => 'required|string',
        ]);

        $jaspel->update([
            'status_validasi' => 'ditolak',
            'validasi_by' => auth()->id(),
            'validasi_at' => now(),
            'catatan_validasi' => $validated['catatan_validasi'],
        ]);

        return response()->json(['message' => 'Jaspel rejected successfully']);
    }

    public function approveUangDuduk(Request $request, UangDuduk $uangDuduk)
    {
        $validated = $request->validate([
            'catatan_validasi' => 'nullable|string',
        ]);

        $uangDuduk->update([
            'status_validasi' => 'disetujui',
            'validasi_by' => auth()->id(),
            'validasi_at' => now(),
            'catatan_validasi' => $validated['catatan_validasi'] ?? null,
        ]);

        return response()->json(['message' => 'Uang duduk approved successfully']);
    }

    public function rejectUangDuduk(Request $request, UangDuduk $uangDuduk)
    {
        $validated = $request->validate([
            'catatan_validasi' => 'required|string',
        ]);

        $uangDuduk->update([
            'status_validasi' => 'ditolak',
            'validasi_by' => auth()->id(),
            'validasi_at' => now(),
            'catatan_validasi' => $validated['catatan_validasi'],
        ]);

        return response()->json(['message' => 'Uang duduk rejected successfully']);
    }
}