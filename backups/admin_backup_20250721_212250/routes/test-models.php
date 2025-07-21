<?php

use Illuminate\Support\Facades\Route;
use App\Models\Pegawai;
use App\Models\User;

Route::get('/test-pegawai-relations', function () {
    $pegawai = Pegawai::with('user.permohonanCutis')->first();
    
    if (!$pegawai) {
        return response()->json([
            'error' => 'No pegawai found',
            'suggestion' => 'Create some pegawai records first'
        ]);
    }
    
    return response()->json([
        'pegawai_id' => $pegawai->id,
        'pegawai_name' => $pegawai->nama_lengkap,
        'has_user' => $pegawai->user ? true : false,
        'user_id' => $pegawai->user?->id,
        'user_email' => $pegawai->user?->email,
        'user_permohonan_cutis_count' => $pegawai->user?->permohonanCutis?->count() ?? 0,
        'raw_relation_check' => [
            'user_relation_loaded' => $pegawai->relationLoaded('user'),
            'permohonan_cutis_relation_loaded' => $pegawai->user?->relationLoaded('permohonanCutis') ?? false,
        ]
    ]);
});

Route::get('/test-widget-data', function () {
    $pegawais = Pegawai::withCount([
        'tindakanAsParamedis as paramedis_procedures' => function($query) {
            $query->where('created_at', '>=', now()->subMonth());
        },
        'tindakanAsNonParamedis as non_paramedis_procedures' => function($query) {
            $query->where('created_at', '>=', now()->subMonth());
        }
    ])
    ->with([
        'user' => function($query) {
            $query->with(['permohonanCutis' => function($q) {
                $q->where('status', 'Disetujui')
                  ->where('created_at', '>=', now()->subMonth());
            }]);
        }
    ])
    ->limit(5)
    ->get();
    
    $results = [];
    foreach ($pegawais as $pegawai) {
        $results[] = [
            'id' => $pegawai->id,
            'name' => $pegawai->nama_lengkap,
            'has_user' => $pegawai->user ? true : false,
            'user_email' => $pegawai->user?->email ?? 'No email',
            'leave_days' => $pegawai->user?->permohonanCutis?->count() ?? 0,
            'paramedis_procedures' => $pegawai->paramedis_procedures_count ?? 0,
            'non_paramedis_procedures' => $pegawai->non_paramedis_procedures_count ?? 0,
        ];
    }
    
    return response()->json([
        'total_pegawai' => $pegawais->count(),
        'sample_data' => $results,
        'query_executed_successfully' => true
    ]);
});