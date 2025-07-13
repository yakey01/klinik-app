<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\TelegramSetting;
use App\Models\SystemConfig;
use App\Services\TelegramService;
use Illuminate\Http\Request;

class TelegramController extends Controller
{
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    public function index()
    {
        $roles = ['petugas', 'bendahara', 'admin', 'manajer'];
        $settings = [];
        
        foreach ($roles as $role) {
            $setting = TelegramSetting::where('role', $role)->first();
            if (!$setting) {
                $setting = TelegramSetting::create([
                    'role' => $role,
                    'chat_id' => null,
                    'notification_types' => TelegramSetting::getRoleNotifications($role),
                    'is_active' => true,
                ]);
            }
            $settings[$role] = $setting;
        }

        $telegramToken = SystemConfig::get('telegram_bot_token', '');
        $notificationTypes = TelegramSetting::getAvailableNotificationTypes();

        return view('settings.telegram.index', compact('settings', 'telegramToken', 'notificationTypes'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'telegram_bot_token' => 'nullable|string',
            'settings' => 'required|array',
            'settings.*.chat_id' => 'nullable|string',
            'settings.*.notification_types' => 'nullable|array',
            'settings.*.is_active' => 'boolean',
        ]);

        // Save telegram bot token
        if ($request->has('telegram_bot_token')) {
            SystemConfig::set('telegram_bot_token', $request->telegram_bot_token, 'telegram', 'Telegram Bot Token');
        }

        // Update settings for each role
        foreach ($request->settings as $role => $data) {
            TelegramSetting::updateOrCreate(
                ['role' => $role],
                [
                    'chat_id' => $data['chat_id'] ?? null,
                    'notification_types' => $data['notification_types'] ?? [],
                    'is_active' => $data['is_active'] ?? false,
                ]
            );
        }

        return redirect()->route('settings.telegram.index')->with('success', 'Pengaturan Telegram berhasil disimpan.');
    }

    public function testNotification(Request $request)
    {
        $request->validate([
            'role' => 'required|string|in:petugas,bendahara,admin,manajer',
            'chat_id' => 'required|string',
        ]);

        try {
            $message = "🤖 Test notifikasi untuk role: {$request->role}\n\n";
            $message .= "✅ Konfigurasi Telegram berhasil!\n";
            $message .= "📅 " . now()->format('d/m/Y H:i:s') . "\n";
            $message .= "🏥 Dokterku - SAHABAT MENUJU SEHAT";

            $result = $this->telegramService->sendMessage($request->chat_id, $message);

            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test notifikasi berhasil dikirim!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengirim test notifikasi. Periksa Chat ID dan Token Bot.'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function getBotInfo()
    {
        try {
            $botInfo = $this->telegramService->getBotInfo();
            return response()->json([
                'success' => true,
                'data' => $botInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}
