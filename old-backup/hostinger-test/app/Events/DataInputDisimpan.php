<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DataInputDisimpan
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Model $data;
    public User $user;
    public string $type;

    /**
     * Create a new event instance.
     */
    public function __construct(Model $data, User $user)
    {
        $this->data = $data;
        $this->user = $user;
        $this->type = class_basename($data);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('data-input'),
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
            'type' => $this->type,
            'data_id' => $this->data->id,
            'user_name' => $this->user->name,
            'user_role' => $this->user->role->display_name,
            'timestamp' => now()->toISOString(),
        ];
    }
}