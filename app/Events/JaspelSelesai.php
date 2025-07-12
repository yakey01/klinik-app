<?php

namespace App\Events;

use App\Models\Tindakan;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class JaspelSelesai
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Tindakan $tindakan;
    public array $jaspelRecords;
    public float $totalJaspel;

    /**
     * Create a new event instance.
     */
    public function __construct(Tindakan $tindakan, array $jaspelRecords)
    {
        $this->tindakan = $tindakan;
        $this->jaspelRecords = $jaspelRecords;
        $this->totalJaspel = collect($jaspelRecords)->sum('nominal');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('jaspel'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'tindakan_id' => $this->tindakan->id,
            'pasien_nama' => $this->tindakan->pasien->nama,
            'jenis_tindakan' => $this->tindakan->jenisTindakan->nama,
            'total_jaspel' => $this->totalJaspel,
            'jaspel_count' => count($this->jaspelRecords),
            'timestamp' => now()->toISOString(),
        ];
    }
}