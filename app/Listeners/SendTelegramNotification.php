<?php

namespace App\Listeners;

use App\Events\IncomeCreated;
use App\Events\PatientCreated;
use App\Events\UserCreated;
use App\Services\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTelegramNotification implements ShouldQueue
{
    use InteractsWithQueue;

    protected NotificationDispatcher $dispatcher;

    public function __construct(NotificationDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function handle(object $event): void
    {
        match (get_class($event)) {
            IncomeCreated::class => $this->dispatcher->dispatchIncomeSuccess($event->data),
            PatientCreated::class => $this->dispatcher->dispatchPatientSuccess($event->data),
            UserCreated::class => $this->dispatcher->dispatchUserAdded($event->data),
            default => null,
        };
    }
}
