<?php

namespace App\Events;

use App\Models\WorkLocation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public WorkLocation $workLocation;
    public array $changedFields;

    /**
     * Create a new event instance.
     */
    public function __construct(WorkLocation $workLocation, array $changedFields = [])
    {
        $this->workLocation = $workLocation;
        $this->changedFields = $changedFields;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('work-location-updates'),
            new Channel('work-location-' . $this->workLocation->id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'work_location' => [
                'id' => $this->workLocation->id,
                'name' => $this->workLocation->name,
                'address' => $this->workLocation->address,
                'latitude' => $this->workLocation->latitude,
                'longitude' => $this->workLocation->longitude,
                'radius_meters' => $this->workLocation->radius_meters,
                'is_active' => $this->workLocation->is_active,
                'updated_at' => $this->workLocation->updated_at?->toISOString(),
            ],
            'changed_fields' => $this->changedFields,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'work.location.updated';
    }
}