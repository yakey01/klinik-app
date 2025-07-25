<?php

namespace App\Jobs;

use App\Models\Tindakan;
use App\Services\Jaspel\JaspelService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProsesJaspelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tindakanId;
    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(int $tindakanId)
    {
        $this->tindakanId = $tindakanId;
    }

    /**
     * Execute the job.
     */
    public function handle(JaspelService $jaspelService): void
    {
        try {
            $tindakan = Tindakan::find($this->tindakanId);
            
            if (!$tindakan) {
                Log::warning("Tindakan dengan ID {$this->tindakanId} tidak ditemukan");
                return;
            }

            if ($tindakan->status !== 'selesai') {
                Log::info("Tindakan ID {$this->tindakanId} belum selesai, skip proses jaspel");
                return;
            }

            // Check if jaspel already exists
            if ($tindakan->jaspel()->exists()) {
                Log::info("Jaspel untuk tindakan ID {$this->tindakanId} sudah ada");
                return;
            }

            // Generate jaspel records
            $jaspelRecords = $jaspelService->generateFromTindakan($this->tindakanId);
            
            Log::info("Berhasil memproses jaspel untuk tindakan ID {$this->tindakanId}", [
                'jaspel_count' => count($jaspelRecords),
                'total_nominal' => collect($jaspelRecords)->sum('nominal')
            ]);

        } catch (Exception $e) {
            Log::error("Gagal memproses jaspel untuk tindakan ID {$this->tindakanId}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("ProsesJaspelJob gagal untuk tindakan ID {$this->tindakanId}: " . $exception->getMessage());
    }
}