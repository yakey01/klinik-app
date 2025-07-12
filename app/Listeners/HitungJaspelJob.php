<?php

namespace App\Listeners;

use App\Events\DataInputDisimpan;
use App\Jobs\ProsesJaspelJob;
use App\Models\Tindakan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class HitungJaspelJob implements ShouldQueue
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
    public function handle(DataInputDisimpan $event): void
    {
        // Only process if the input is a completed Tindakan
        if ($event->data instanceof Tindakan && $event->data->status === 'selesai') {
            // Dispatch job to process jaspel calculation
            ProsesJaspelJob::dispatch($event->data->id)
                ->delay(now()->addMinutes(5)); // Delay 5 minutes to allow for any corrections
        }
    }
}