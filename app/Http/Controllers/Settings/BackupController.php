<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\JenisTindakan;
use App\Models\Pegawai;
use App\Models\Dokter;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MasterDataExport;

class BackupController extends Controller
{
    public function index()
    {
        return view('settings.backup.index');
    }

    public function exportMasterData()
    {
        return Excel::download(new MasterDataExport, 'master-data-' . date('Y-m-d-H-i-s') . '.xlsx');
    }

    public function exportJson()
    {
        $data = [
            'users' => User::with('role')->get(),
            'roles' => Role::all(),
            'jenis_tindakan' => JenisTindakan::all(),
            'pegawai' => Pegawai::all(),
            'dokter' => Dokter::all(),
            'exported_at' => now(),
        ];

        $filename = 'master-data-' . date('Y-m-d-H-i-s') . '.json';
        
        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Content-Type', 'application/json');
    }

    public function importJson(Request $request)
    {
        $request->validate([
            'json_file' => 'required|file|mimes:json',
        ]);

        try {
            $jsonContent = file_get_contents($request->file('json_file')->path());
            $data = json_decode($jsonContent, true);

            if (!$data || !isset($data['users']) || !isset($data['roles'])) {
                return redirect()->back()->with('error', 'Format file JSON tidak valid.');
            }

            // Import roles first
            foreach ($data['roles'] as $roleData) {
                Role::updateOrCreate(
                    ['name' => $roleData['name']],
                    $roleData
                );
            }

            // Import users
            foreach ($data['users'] as $userData) {
                unset($userData['id']); // Remove ID to avoid conflicts
                User::updateOrCreate(
                    ['email' => $userData['email']],
                    $userData
                );
            }

            return redirect()->route('settings.backup.index')->with('success', 'Data berhasil diimpor.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengimpor data: ' . $e->getMessage());
        }
    }
}
