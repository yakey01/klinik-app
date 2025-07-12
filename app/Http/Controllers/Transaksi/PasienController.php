<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Repositories\PasienRepository;
use Illuminate\Http\Request;

class PasienController extends Controller
{
    protected $pasienRepository;

    public function __construct(PasienRepository $pasienRepository)
    {
        $this->pasienRepository = $pasienRepository;
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        
        $pasien = $this->pasienRepository->paginate($perPage, $search);
        return response()->json($pasien);
    }

    public function show(Pasien $pasien)
    {
        $pasien->load(['tindakan' => function ($query) {
            $query->with(['jenisTindakan', 'dokter', 'shift'])->latest();
        }]);
        
        return response()->json($pasien);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'no_rekam_medis' => 'required|string|unique:pasien,no_rekam_medis',
            'nama' => 'required|string',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string',
            'email' => 'nullable|email',
            'pekerjaan' => 'nullable|string',
            'status_pernikahan' => 'nullable|in:belum_menikah,menikah,janda,duda',
            'kontak_darurat_nama' => 'nullable|string',
            'kontak_darurat_telepon' => 'nullable|string',
        ]);

        $pasien = $this->pasienRepository->create($validated);
        return response()->json($pasien, 201);
    }

    public function update(Request $request, Pasien $pasien)
    {
        $validated = $request->validate([
            'no_rekam_medis' => 'required|string|unique:pasien,no_rekam_medis,' . $pasien->id,
            'nama' => 'required|string',
            'tanggal_lahir' => 'required|date',
            'jenis_kelamin' => 'required|in:L,P',
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string',
            'email' => 'nullable|email',
            'pekerjaan' => 'nullable|string',
            'status_pernikahan' => 'nullable|in:belum_menikah,menikah,janda,duda',
            'kontak_darurat_nama' => 'nullable|string',
            'kontak_darurat_telepon' => 'nullable|string',
        ]);

        $pasien = $this->pasienRepository->update($pasien, $validated);
        return response()->json($pasien);
    }

    public function destroy(Pasien $pasien)
    {
        $this->pasienRepository->delete($pasien);
        return response()->json(['message' => 'Pasien deleted successfully']);
    }

    public function searchByNoRekamMedis($noRekamMedis)
    {
        $pasien = $this->pasienRepository->findByNoRekamMedis($noRekamMedis);
        
        if (!$pasien) {
            return response()->json(['message' => 'Pasien not found'], 404);
        }
        
        return response()->json($pasien);
    }
}