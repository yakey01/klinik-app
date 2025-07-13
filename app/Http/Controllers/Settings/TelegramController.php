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
        $telegramSettings = TelegramSetting::all();
        $adminChatId = SystemConfig::where('key', 'TELEGRAM_ADMIN_CHAT_ID')->value('value');
        
        return view('settings.telegram.simple', compact('telegramSettings', 'adminChatId'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'admin_chat_id' => 'nullable|string',
            'roles' => 'nullable|array',
            'roles.*.chat_id' => 'nullable|string',
            'roles.*.notifications' => 'nullable|array',
            'roles.*.is_active' => 'nullable|boolean',
        ]);

        // Save admin chat ID
        if ($request->has('admin_chat_id')) {
            SystemConfig::updateOrCreate(
                ['key' => 'TELEGRAM_ADMIN_CHAT_ID'],
                [
                    'value' => $request->admin_chat_id,
                    'description' => 'Chat ID admin utama untuk fallback notifikasi',
                    'type' => 'text'
                ]
            );
        }

        // Update settings for each role
        if ($request->has('roles')) {
            foreach ($request->roles as $role => $data) {
                TelegramSetting::updateOrCreate(
                    ['role' => $role],
                    [
                        'chat_id' => $data['chat_id'] ?? null,
                        'notification_types' => $data['notifications'] ?? [],
                        'is_active' => isset($data['is_active']) ? true : false,
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan Telegram berhasil disimpan.'
        ]);
    }

    public function testNotification(Request $request)
    {
        $request->validate([
            'chat_id' => 'required|string',
            'message' => 'nullable|string',
        ]);

        try {
            $message = $request->message ?? "🧪 Test koneksi Telegram Bot berhasil!\n\nBot: Dokterku\nWaktu: " . now()->format('d M Y H:i:s');

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
