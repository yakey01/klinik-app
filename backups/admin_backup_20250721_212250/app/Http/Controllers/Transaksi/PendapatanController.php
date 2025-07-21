<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Pendapatan;
use App\Services\Transaksi\PendapatanService;
use Illuminate\Http\Request;

class PendapatanController extends Controller
{
    protected $pendapatanService;

    public function __construct(PendapatanService $pendapatanService)
    {
        $this->pendapatanService = $pendapatanService;
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $filters = $request->only(['tanggal_dari', 'tanggal_sampai', 'kategori', 'status_validasi']);
        
        $pendapatan = $this->pendapatanService->paginate($perPage, $filters);
        return response()->json($pendapatan);
    }

    public function show(Pendapatan $pendapatan)
    {
        $pendapatan->load(['tindakan', 'inputBy', 'validasiBy']);
        return response()->json($pendapatan);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'keterangan' => 'required|string',
            'nominal' => 'required|numeric|min:0',
            'kategori' => 'required|in:tindakan,konsultasi,obat,administrasi,lainnya',
            'tindakan_id' => 'nullable|exists:tindakan,id',
        ]);

        $pendapatan = $this->pendapatanService->create($validated);
        return response()->json($pendapatan, 201);
    }

    public function update(Request $request, Pendapatan $pendapatan)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'keterangan' => 'required|string',
            'nominal' => 'required|numeric|min:0',
            'kategori' => 'required|in:tindakan,konsultasi,obat,administrasi,lainnya',
            'tindakan_id' => 'nullable|exists:tindakan,id',
        ]);

        $pendapatan = $this->pendapatanService->update($pendapatan, $validated);
        return response()->json($pendapatan);
    }

    public function destroy(Pendapatan $pendapatan)
    {
        $this->pendapatanService->delete($pendapatan);
        return response()->json(['message' => 'Pendapatan deleted successfully']);
    }

    public function getSummary(Request $request)
    {
        $filters = $request->only(['tanggal_dari', 'tanggal_sampai', 'kategori']);
        $summary = $this->pendapatanService->getSummary($filters);
        
        return response()->json($summary);
    }

    public function getByKategori($kategori, Request $request)
    {
        $filters = $request->only(['tanggal_dari', 'tanggal_sampai']);
        $pendapatan = $this->pendapatanService->getByKategori($kategori, $filters);
        
        return response()->json($pendapatan);
    }
}