<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Pengeluaran;
use App\Services\Transaksi\PengeluaranService;
use Illuminate\Http\Request;

class PengeluaranController extends Controller
{
    protected $pengeluaranService;

    public function __construct(PengeluaranService $pengeluaranService)
    {
        $this->pengeluaranService = $pengeluaranService;
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $filters = $request->only(['tanggal_dari', 'tanggal_sampai', 'kategori', 'status_validasi']);
        
        $pengeluaran = $this->pengeluaranService->paginate($perPage, $filters);
        return response()->json($pengeluaran);
    }

    public function show(Pengeluaran $pengeluaran)
    {
        $pengeluaran->load(['inputBy', 'validasiBy']);
        return response()->json($pengeluaran);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'keterangan' => 'required|string',
            'nominal' => 'required|numeric|min:0',
            'kategori' => 'required|in:operasional,gaji,jaspel,uang_duduk,inventaris,lainnya',
            'bukti_transaksi' => 'nullable|string',
        ]);

        $pengeluaran = $this->pengeluaranService->create($validated);
        return response()->json($pengeluaran, 201);
    }

    public function update(Request $request, Pengeluaran $pengeluaran)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'keterangan' => 'required|string',
            'nominal' => 'required|numeric|min:0',
            'kategori' => 'required|in:operasional,gaji,jaspel,uang_duduk,inventaris,lainnya',
            'bukti_transaksi' => 'nullable|string',
        ]);

        $pengeluaran = $this->pengeluaranService->update($pengeluaran, $validated);
        return response()->json($pengeluaran);
    }

    public function destroy(Pengeluaran $pengeluaran)
    {
        $this->pengeluaranService->delete($pengeluaran);
        return response()->json(['message' => 'Pengeluaran deleted successfully']);
    }

    public function getSummary(Request $request)
    {
        $filters = $request->only(['tanggal_dari', 'tanggal_sampai', 'kategori']);
        $summary = $this->pengeluaranService->getSummary($filters);
        
        return response()->json($summary);
    }

    public function getByKategori($kategori, Request $request)
    {
        $filters = $request->only(['tanggal_dari', 'tanggal_sampai']);
        $pengeluaran = $this->pengeluaranService->getByKategori($kategori, $filters);
        
        return response()->json($pengeluaran);
    }
}