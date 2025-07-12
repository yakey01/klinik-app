<?php

namespace App\Http\Controllers\Jaspel;

use App\Http\Controllers\Controller;
use App\Models\Jaspel;
use App\Repositories\JaspelRepository;
use App\Services\Jaspel\JaspelService;
use Illuminate\Http\Request;

class JaspelController extends Controller
{
    protected $jaspelRepository;
    protected $jaspelService;

    public function __construct(
        JaspelRepository $jaspelRepository,
        JaspelService $jaspelService
    ) {
        $this->jaspelRepository = $jaspelRepository;
        $this->jaspelService = $jaspelService;
    }

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $filters = $request->only(['tanggal_dari', 'tanggal_sampai', 'user_id', 'jenis_jaspel', 'status_validasi']);
        
        $jaspel = $this->jaspelRepository->paginate($perPage, $filters);
        return response()->json($jaspel);
    }

    public function show(Jaspel $jaspel)
    {
        $jaspel->load(['user', 'tindakan', 'shift', 'inputBy', 'validasiBy']);
        return response()->json($jaspel);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tindakan_id' => 'required|exists:tindakan,id',
            'user_id' => 'required|exists:users,id',
            'jenis_jaspel' => 'required|in:dokter,paramedis,non_paramedis',
            'nominal' => 'required|numeric|min:0',
            'tanggal' => 'required|date',
            'shift_id' => 'required|exists:shifts,id',
        ]);

        $jaspel = $this->jaspelService->create($validated);
        return response()->json($jaspel, 201);
    }

    public function update(Request $request, Jaspel $jaspel)
    {
        $validated = $request->validate([
            'tindakan_id' => 'required|exists:tindakan,id',
            'user_id' => 'required|exists:users,id',
            'jenis_jaspel' => 'required|in:dokter,paramedis,non_paramedis',
            'nominal' => 'required|numeric|min:0',
            'tanggal' => 'required|date',
            'shift_id' => 'required|exists:shifts,id',
        ]);

        $jaspel = $this->jaspelService->update($jaspel, $validated);
        return response()->json($jaspel);
    }

    public function destroy(Jaspel $jaspel)
    {
        $this->jaspelService->delete($jaspel);
        return response()->json(['message' => 'Jaspel deleted successfully']);
    }

    public function getByUser($userId, Request $request)
    {
        $filters = $request->only(['tanggal_dari', 'tanggal_sampai', 'jenis_jaspel', 'status_validasi']);
        $jaspel = $this->jaspelRepository->getByUser($userId, $filters);
        
        return response()->json($jaspel);
    }

    public function getSummaryByUser($userId, Request $request)
    {
        $filters = $request->only(['tanggal_dari', 'tanggal_sampai']);
        $summary = $this->jaspelService->getSummaryByUser($userId, $filters);
        
        return response()->json($summary);
    }

    public function getRekapJaspel(Request $request)
    {
        $filters = $request->only(['tanggal_dari', 'tanggal_sampai', 'jenis_jaspel']);
        $rekap = $this->jaspelService->getRekapJaspel($filters);
        
        return response()->json($rekap);
    }

    public function generateFromTindakan(Request $request)
    {
        $validated = $request->validate([
            'tindakan_id' => 'required|exists:tindakan,id',
        ]);

        $jaspel = $this->jaspelService->generateFromTindakan($validated['tindakan_id']);
        return response()->json($jaspel);
    }
}