<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\JenisTindakan;
use Illuminate\Http\Request;

class JenisTindakanController extends Controller
{
    public function index()
    {
        $jenisTindakan = JenisTindakan::active()->get();
        return response()->json($jenisTindakan);
    }

    public function show(JenisTindakan $jenisTindakan)
    {
        return response()->json($jenisTindakan);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required|string|unique:jenis_tindakan,kode',
            'nama' => 'required|string',
            'deskripsi' => 'nullable|string',
            'tarif' => 'required|numeric|min:0',
            'jasa_dokter' => 'required|numeric|min:0',
            'jasa_paramedis' => 'required|numeric|min:0',
            'jasa_non_paramedis' => 'required|numeric|min:0',
            'kategori' => 'required|in:konsultasi,pemeriksaan,tindakan,obat,lainnya',
        ]);

        $jenisTindakan = JenisTindakan::create($validated);
        return response()->json($jenisTindakan, 201);
    }

    public function update(Request $request, JenisTindakan $jenisTindakan)
    {
        $validated = $request->validate([
            'kode' => 'required|string|unique:jenis_tindakan,kode,' . $jenisTindakan->id,
            'nama' => 'required|string',
            'deskripsi' => 'nullable|string',
            'tarif' => 'required|numeric|min:0',
            'jasa_dokter' => 'required|numeric|min:0',
            'jasa_paramedis' => 'required|numeric|min:0',
            'jasa_non_paramedis' => 'required|numeric|min:0',
            'kategori' => 'required|in:konsultasi,pemeriksaan,tindakan,obat,lainnya',
            'is_active' => 'boolean',
        ]);

        $jenisTindakan->update($validated);
        return response()->json($jenisTindakan);
    }

    public function destroy(JenisTindakan $jenisTindakan)
    {
        $jenisTindakan->update(['is_active' => false]);
        return response()->json(['message' => 'Jenis tindakan deactivated successfully']);
    }

    public function getByKategori($kategori)
    {
        $jenisTindakan = JenisTindakan::active()->byKategori($kategori)->get();
        return response()->json($jenisTindakan);
    }
}