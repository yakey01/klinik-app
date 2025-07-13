<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncomeCreated
{
    use Dispatchable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }
}
