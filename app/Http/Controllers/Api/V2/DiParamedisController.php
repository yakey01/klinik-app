<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\DiParamedis;
use App\Models\Pegawai;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DiParamedisController extends Controller
{
    /**
     * Get list of DI Paramedis for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->pegawai || $user->pegawai->jenis_pegawai !== 'Paramedis') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya paramedis yang dapat mengakses.',
            ], 403);
        }

        $query = DiParamedis::where('pegawai_id', $user->pegawai_id)
            ->with(['jadwalJaga', 'approvedBy']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('tanggal', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        }

        // Filter by month
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth('tanggal', $request->month)
                  ->whereYear('tanggal', $request->year);
        }

        $diParamedis = $query->orderBy('tanggal', 'desc')
                            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $diParamedis->items(),
            'pagination' => [
                'current_page' => $diParamedis->currentPage(),
                'last_page' => $diParamedis->lastPage(),
                'per_page' => $diParamedis->perPage(),
                'total' => $diParamedis->total(),
            ],
        ]);
    }

    /**
     * Get single DI Paramedis detail
     */
    public function show($id): JsonResponse
    {
        $user = Auth::user();
        
        $diParamedis = DiParamedis::with(['pegawai', 'jadwalJaga', 'approvedBy'])
            ->find($id);

        if (!$diParamedis) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        // Check ownership or admin role
        if ($diParamedis->pegawai_id !== $user->pegawai_id && 
            !$user->hasRole(['admin', 'manajer'])) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke data ini.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $diParamedis,
        ]);
    }

    /**
     * Create new DI Paramedis
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->pegawai || $user->pegawai->jenis_pegawai !== 'Paramedis') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya paramedis yang dapat membuat daftar isian.',
            ], 403);
        }

        // Check if already exists for the date
        $existingDI = DiParamedis::where('pegawai_id', $user->pegawai_id)
            ->whereDate('tanggal', Carbon::parse($request->tanggal)->toDateString())
            ->first();

        if ($existingDI) {
            return response()->json([
                'success' => false,
                'message' => 'Anda sudah memiliki daftar isian untuk tanggal tersebut.',
                'existing_id' => $existingDI->id,
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i',
            'shift' => 'required|in:Pagi,Siang,Malam',
            'lokasi_tugas' => 'required|string|max:255',
            'jadwal_jaga_id' => 'nullable|exists:jadwal_jaga,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $diParamedis = DiParamedis::create([
                'pegawai_id' => $user->pegawai_id,
                'user_id' => $user->id,
                'tanggal' => $request->tanggal,
                'jam_mulai' => $request->jam_mulai,
                'shift' => $request->shift,
                'lokasi_tugas' => $request->lokasi_tugas,
                'jadwal_jaga_id' => $request->jadwal_jaga_id,
                'status' => 'draft',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Daftar isian berhasil dibuat.',
                'data' => $diParamedis->load(['pegawai', 'jadwalJaga']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat daftar isian.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update DI Paramedis
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        $diParamedis = DiParamedis::find($id);

        if (!$diParamedis) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        // Check ownership
        if ($diParamedis->pegawai_id !== $user->pegawai_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengedit data ini.',
            ], 403);
        }

        // Check if editable
        if (!$diParamedis->is_editable) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak dapat diedit. Status: ' . $diParamedis->status_text,
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'jam_selesai' => 'nullable|date_format:H:i',
            'jumlah_pasien_dilayani' => 'nullable|integer|min:0',
            'jumlah_tindakan_medis' => 'nullable|integer|min:0',
            'jumlah_observasi_pasien' => 'nullable|integer|min:0',
            'jumlah_kasus_emergency' => 'nullable|integer|min:0',
            'tindakan_medis' => 'nullable|array',
            'obat_diberikan' => 'nullable|array',
            'alat_medis_digunakan' => 'nullable|array',
            'catatan_kasus_emergency' => 'nullable|string',
            'laporan_kegiatan' => 'nullable|string',
            'kendala_hambatan' => 'nullable|string',
            'saran_perbaikan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $diParamedis->update($request->only([
                'jam_selesai',
                'jumlah_pasien_dilayani',
                'jumlah_tindakan_medis',
                'jumlah_observasi_pasien',
                'jumlah_kasus_emergency',
                'tindakan_medis',
                'obat_diberikan',
                'alat_medis_digunakan',
                'catatan_kasus_emergency',
                'laporan_kegiatan',
                'kendala_hambatan',
                'saran_perbaikan',
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diperbarui.',
                'data' => $diParamedis->fresh(['pegawai', 'jadwalJaga']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit DI Paramedis for approval
     */
    public function submit($id): JsonResponse
    {
        $user = Auth::user();
        $diParamedis = DiParamedis::find($id);

        if (!$diParamedis) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        // Check ownership
        if ($diParamedis->pegawai_id !== $user->pegawai_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk submit data ini.',
            ], 403);
        }

        if ($diParamedis->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya data dengan status draft yang dapat disubmit.',
            ], 400);
        }

        // Validate required fields
        if ($diParamedis->jumlah_pasien_dilayani === null ||
            $diParamedis->jumlah_tindakan_medis === null ||
            $diParamedis->jumlah_observasi_pasien === null ||
            empty($diParamedis->laporan_kegiatan)) {
            return response()->json([
                'success' => false,
                'message' => 'Harap lengkapi semua data yang diperlukan sebelum submit.',
                'required_fields' => [
                    'jumlah_pasien_dilayani',
                    'jumlah_tindakan_medis',
                    'jumlah_observasi_pasien',
                    'laporan_kegiatan',
                ],
            ], 400);
        }

        DB::beginTransaction();
        try {
            $diParamedis->submit();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disubmit dan menunggu persetujuan.',
                'data' => $diParamedis->fresh(['pegawai', 'jadwalJaga']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal submit data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add medical procedure
     */
    public function addTindakanMedis(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        $diParamedis = DiParamedis::find($id);

        if (!$diParamedis) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        // Check ownership and editability
        if ($diParamedis->pegawai_id !== $user->pegawai_id || !$diParamedis->is_editable) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menambah tindakan pada data ini.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama_tindakan' => 'required|string|max:255',
            'jumlah' => 'nullable|integer|min:1',
            'keterangan' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $diParamedis->addTindakanMedis($request->only(['nama_tindakan', 'jumlah', 'keterangan']));

        return response()->json([
            'success' => true,
            'message' => 'Tindakan medis berhasil ditambahkan.',
            'data' => $diParamedis->fresh(),
        ]);
    }

    /**
     * Add medication given
     */
    public function addObat(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        $diParamedis = DiParamedis::find($id);

        if (!$diParamedis) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        // Check ownership and editability
        if ($diParamedis->pegawai_id !== $user->pegawai_id || !$diParamedis->is_editable) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menambah obat pada data ini.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama_obat' => 'required|string|max:255',
            'dosis' => 'nullable|string|max:100',
            'jumlah' => 'nullable|integer|min:1',
            'cara_pemberian' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $diParamedis->addObatDiberikan($request->only(['nama_obat', 'dosis', 'jumlah', 'cara_pemberian']));

        return response()->json([
            'success' => true,
            'message' => 'Obat berhasil ditambahkan.',
            'data' => $diParamedis->fresh(),
        ]);
    }

    /**
     * Upload signature
     */
    public function uploadSignature(Request $request, $id): JsonResponse
    {
        $user = Auth::user();
        $diParamedis = DiParamedis::find($id);

        if (!$diParamedis) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        // Check ownership
        if ($diParamedis->pegawai_id !== $user->pegawai_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk upload signature.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'signature' => 'required|string', // Base64 image
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Decode base64 signature
            $signatureData = $request->signature;
            if (preg_match('/^data:image\/(\w+);base64,/', $signatureData, $type)) {
                $signatureData = substr($signatureData, strpos($signatureData, ',') + 1);
                $type = strtolower($type[1]); // jpg, png, gif
                
                if (!in_array($type, ['jpg', 'jpeg', 'png'])) {
                    throw new \Exception('Invalid image type');
                }
                
                $signatureData = base64_decode($signatureData);
                
                if ($signatureData === false) {
                    throw new \Exception('Base64 decode failed');
                }
                
                $fileName = 'signatures/di_paramedis_' . $diParamedis->id . '_' . time() . '.' . $type;
                
                Storage::disk('public')->put($fileName, $signatureData);
                
                // Delete old signature if exists
                if ($diParamedis->signature_path) {
                    Storage::disk('public')->delete($diParamedis->signature_path);
                }
                
                $diParamedis->update(['signature_path' => $fileName]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Tanda tangan berhasil diupload.',
                    'signature_url' => Storage::disk('public')->url($fileName),
                ]);
            } else {
                throw new \Exception('Invalid signature format');
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal upload tanda tangan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get summary statistics
     */
    public function summary(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->pegawai || $user->pegawai->jenis_pegawai !== 'Paramedis') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;

        $summary = DiParamedis::where('pegawai_id', $user->pegawai_id)
            ->whereMonth('tanggal', $month)
            ->whereYear('tanggal', $year)
            ->selectRaw('
                COUNT(*) as total_hari,
                SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as total_draft,
                SUM(CASE WHEN status = "submitted" THEN 1 ELSE 0 END) as total_pending,
                SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as total_approved,
                SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as total_rejected,
                SUM(jumlah_pasien_dilayani) as total_pasien,
                SUM(jumlah_tindakan_medis) as total_tindakan,
                SUM(jumlah_observasi_pasien) as total_observasi,
                SUM(jumlah_kasus_emergency) as total_emergency
            ')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'month' => $month,
                    'year' => $year,
                    'month_name' => Carbon::create($year, $month)->format('F'),
                ],
                'summary' => $summary,
            ],
        ]);
    }
}