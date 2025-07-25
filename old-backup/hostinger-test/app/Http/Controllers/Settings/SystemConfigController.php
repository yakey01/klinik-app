<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SystemConfigController extends Controller
{
    public function index()
    {
        $configs = [
            'general' => SystemConfig::getAllByCategory('general'),
            'security' => SystemConfig::getAllByCategory('security'),
            'branding' => SystemConfig::getAllByCategory('branding'),
            'schedule' => SystemConfig::getAllByCategory('schedule'),
        ];
        
        return view('settings.config.index', compact('configs'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'clinic_name' => 'required|string|max:255',
            'clinic_address' => 'nullable|string',
            'clinic_phone' => 'nullable|string|max:20',
            'clinic_motto' => 'nullable|string|max:255',
            'work_start_time' => 'required|date_format:H:i',
            'work_end_time' => 'required|date_format:H:i',
            'holidays' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Save general settings
        SystemConfig::set('clinic_name', $request->clinic_name, 'branding', 'Nama klinik');
        SystemConfig::set('clinic_address', $request->clinic_address, 'branding', 'Alamat klinik');
        SystemConfig::set('clinic_phone', $request->clinic_phone, 'branding', 'Telepon klinik');
        SystemConfig::set('clinic_motto', $request->clinic_motto, 'branding', 'Motto klinik');
        
        // Save schedule settings
        SystemConfig::set('work_start_time', $request->work_start_time, 'schedule', 'Jam mulai kerja');
        SystemConfig::set('work_end_time', $request->work_end_time, 'schedule', 'Jam selesai kerja');
        SystemConfig::set('holidays', $request->holidays, 'schedule', 'Hari libur (JSON)');

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
            SystemConfig::set('clinic_logo', $logoPath, 'branding', 'Logo klinik');
        }

        return redirect()->route('settings.config.index')->with('success', 'Konfigurasi berhasil diperbarui.');
    }

    public function updateSecurity(Request $request)
    {
        $request->validate([
            'password_min_length' => 'required|integer|min:6|max:20',
            'password_require_uppercase' => 'boolean',
            'password_require_numbers' => 'boolean',
            'session_timeout' => 'required|integer|min:15|max:1440',
            'max_login_attempts' => 'required|integer|min:3|max:10',
        ]);

        SystemConfig::set('password_min_length', $request->password_min_length, 'security', 'Panjang minimum password');
        SystemConfig::set('password_require_uppercase', $request->boolean('password_require_uppercase'), 'security', 'Wajib huruf besar');
        SystemConfig::set('password_require_numbers', $request->boolean('password_require_numbers'), 'security', 'Wajib angka');
        SystemConfig::set('session_timeout', $request->session_timeout, 'security', 'Timeout sesi (menit)');
        SystemConfig::set('max_login_attempts', $request->max_login_attempts, 'security', 'Maksimal percobaan login');

        return redirect()->route('settings.config.index')->with('success', 'Pengaturan keamanan berhasil diperbarui.');
    }
}
