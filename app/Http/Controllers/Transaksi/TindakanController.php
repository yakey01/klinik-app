<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Tindakan;
use App\Repositories\TindakanRepository;
use App\Services\Transaksi\TindakanService;
use Illuminate\Http\Request;

class TindakanController extends Controller
{
    protected $tindakanRepository;
    protected $tindakanService;

    public function __construct(
        TindakanRepository $tindakanRepository,
        TindakanService $tindakanService
    ) {
        $this->tindakanRepository = $tindakanRepository;
        $this->tindakanService = $tindakanService;
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $filters = $request->only(['tanggal_dari', 'tanggal_sampai', 'dokter_id', 'status']);
        
        $tindakan = $this->tindakanRepository->paginate($perPage, $filters);
        return response()->json($tindakan);
    }

    public function show(Tindakan $tindakan)
    {
        $tindakan->load([
            'pasien', 
            'jenisTindakan', 
            'dokter', 
            'paramedis', 
            'nonParamedis', 
            'shift', 
            'jaspel'
        ]);
        
        return response()->json($tindakan);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'pasien_id' => 'required|exists:pasien,id',
            'jenis_tindakan_id' => 'required|exists:jenis_tindakan,id',
            'dokter_id' => 'required|exists:users,id',
            'paramedis_id' => 'nullable|exists:users,id',
            'non_paramedis_id' => 'nullable|exists:users,id',
            'shift_id' => 'required|exists:shifts,id',
            'tanggal_tindakan' => 'required|date',
            'catatan' => 'nullable|string',
        ]);

        $tindakan = $this->tindakanService->create($validated);
        return response()->json($tindakan, 201);
    }

    public function update(Request $request, Tindakan $tindakan)
    {
        $validated = $request->validate([
            'pasien_id' => 'required|exists:pasien,id',
            'jenis_tindakan_id' => 'required|exists:jenis_tindakan,id',
            'dokter_id' => 'required|exists:users,id',
            'paramedis_id' => 'nullable|exists:users,id',
            'non_paramedis_id' => 'nullable|exists:users,id',
            'shift_id' => 'required|exists:shifts,id',
            'tanggal_tindakan' => 'required|date',
            'catatan' => 'nullable|string',
            'status' => 'required|in:pending,selesai,batal',
        ]);

        $tindakan = $this->tindakanService->update($tindakan, $validated);
        return response()->json($tindakan);
    }

    public function destroy(Tindakan $tindakan)
    {
        $this->tindakanService->delete($tindakan);
        return response()->json(['message' => 'Tindakan deleted successfully']);
    }

    public function complete(Tindakan $tindakan)
    {
        $tindakan = $this->tindakanService->complete($tindakan);
        return response()->json($tindakan);
    }

    public function cancel(Tindakan $tindakan)
    {
        $tindakan = $this->tindakanService->cancel($tindakan);
        return response()->json($tindakan);
    }

    public function getByDokter($dokterId, Request $request)
    {
        $filters = $request->only(['tanggal_dari', 'tanggal_sampai']);
        $tindakan = $this->tindakanRepository->getByDokter($dokterId, $filters);
        
        return response()->json($tindakan);
    }
}