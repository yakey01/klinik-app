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

class ValidasiBerhasil
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Model $data;
    public User $validator;
    public string $type;
    public string $status;

    /**
     * Create a new event instance.
     */
    public function __construct(Model $data, User $validator, string $status)
    {
        $this->data = $data;
        $this->validator = $validator;
        $this->type = class_basename($data);
        $this->status = $status;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('validation'),
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
            'status' => $this->status,
            'validator_name' => $this->validator->name,
            'validator_role' => $this->validator->role->display_name,
            'timestamp' => now()->toISOString(),
        ];
    }
}