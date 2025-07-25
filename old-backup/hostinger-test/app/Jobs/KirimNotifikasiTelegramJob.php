<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KirimNotifikasiTelegramJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $message;
    public int $tries = 3;
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $botToken = config('services.telegram.bot_token');
            $chatId = config('services.telegram.chat_id');

            if (!$botToken || !$chatId) {
                Log::warning('Telegram bot token atau chat ID tidak dikonfigurasi');
                return;
            }

            $response = Http::timeout($this->timeout)
                ->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $this->message,
                    'parse_mode' => 'Markdown',
                    'disable_web_page_preview' => true,
                ]);

            if ($response->successful()) {
                Log::info('Notifikasi Telegram berhasil dikirim');
            } else {
                throw new Exception('Gagal mengirim notifikasi Telegram: ' . $response->body());
            }

        } catch (Exception $e) {
            Log::error('Gagal mengirim notifikasi Telegram: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('KirimNotifikasiTelegramJob gagal: ' . $exception->getMessage());
    }
}