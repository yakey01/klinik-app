<?php

namespace App\Listeners;

use App\Events\DataInputDisimpan;
use App\Events\JaspelSelesai;
use App\Events\ValidasiBerhasil;
use App\Jobs\KirimNotifikasiTelegramJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class KirimNotifikasiTelegram implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DataInputDisimpan|ValidasiBerhasil|JaspelSelesai $event): void
    {
        $message = $this->generateMessage($event);
        
        if ($message) {
            KirimNotifikasiTelegramJob::dispatch($message);
        }
    }

    /**
     * Generate notification message based on event type
     */
    protected function generateMessage(DataInputDisimpan|ValidasiBerhasil|JaspelSelesai $event): ?string
    {
        return match (get_class($event)) {
            DataInputDisimpan::class => $this->generateDataInputMessage($event),
            ValidasiBerhasil::class => $this->generateValidationMessage($event),
            JaspelSelesai::class => $this->generateJaspelMessage($event),
            default => null
        };
    }

    /**
     * Generate message for data input events
     */
    protected function generateDataInputMessage(DataInputDisimpan $event): string
    {
        return "📝 *Data Input Disimpan*\n\n" .
               "Tipe: {$event->type}\n" .
               "Input oleh: {$event->user->name} ({$event->user->role->display_name})\n" .
               "Waktu: " . now()->format('d/m/Y H:i:s') . "\n\n" .
               "Data berhasil disimpan dan menunggu validasi.";
    }

    /**
     * Generate message for validation events
     */
    protected function generateValidationMessage(ValidasiBerhasil $event): string
    {
        $statusText = $event->status === 'disetujui' ? '✅ Disetujui' : '❌ Ditolak';
        
        return "🔍 *Validasi Berhasil*\n\n" .
               "Tipe: {$event->type}\n" .
               "Status: {$statusText}\n" .
               "Validator: {$event->validator->name} ({$event->validator->role->display_name})\n" .
               "Waktu: " . now()->format('d/m/Y H:i:s');
    }

    /**
     * Generate message for jaspel completion events
     */
    protected function generateJaspelMessage(JaspelSelesai $event): string
    {
        return "💰 *Jaspel Selesai*\n\n" .
               "Tindakan: {$event->tindakan->jenisTindakan->nama}\n" .
               "Pasien: {$event->tindakan->pasien->nama}\n" .
               "Total Jaspel: Rp " . number_format($event->totalJaspel, 0, ',', '.') . "\n" .
               "Jumlah Record: {$event->jaspelRecords} record\n" .
               "Waktu: " . now()->format('d/m/Y H:i:s') . "\n\n" .
               "Jaspel berhasil dibuat dan menunggu validasi.";
    }
}