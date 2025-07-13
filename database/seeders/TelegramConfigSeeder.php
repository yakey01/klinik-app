<?php

namespace Database\Seeders;

use App\Models\SystemConfig;
use Illuminate\Database\Seeder;

class TelegramConfigSeeder extends Seeder
{
    public function run(): void
    {
        $configs = [
            [
                'key' => 'TELEGRAM_BOT_TOKEN',
                'value' => env('TELEGRAM_BOT_TOKEN', ''),
                'description' => 'Token bot Telegram dari BotFather',
                'category' => 'telegram'
            ],
            [
                'key' => 'TELEGRAM_ADMIN_CHAT_ID', 
                'value' => env('TELEGRAM_ADMIN_CHAT_ID', ''),
                'description' => 'Chat ID admin utama untuk fallback notifikasi',
                'category' => 'telegram'
            ],
            [
                'key' => 'TELEGRAM_BOT_NAME',
                'value' => 'Dokterku Bot',
                'description' => 'Nama bot yang ditampilkan di notifikasi',
                'category' => 'telegram'
            ],
            [
                'key' => 'TELEGRAM_NOTIFICATIONS_ENABLED',
                'value' => 'true',
                'description' => 'Enable/disable semua notifikasi Telegram',
                'category' => 'telegram'
            ]
        ];

        foreach ($configs as $config) {
            SystemConfig::updateOrCreate(
                ['key' => $config['key']],
                $config
            );
        }
    }
}