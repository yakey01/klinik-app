<?php

namespace App\Console\Commands;

use App\Services\NotificationDispatcher;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use Illuminate\Console\Command;

class SendWeeklyRecap extends Command
{
    protected $signature = 'telegram:weekly-recap';
    protected $description = 'Send weekly recap notifications via Telegram';

    protected NotificationDispatcher $dispatcher;

    public function __construct(NotificationDispatcher $dispatcher)
    {
        parent::__construct();
        $this->dispatcher = $dispatcher;
    }

    public function handle()
    {
        $startOfWeek = now()->startOfWeek()->format('Y-m-d');
        $endOfWeek = now()->endOfWeek()->format('Y-m-d');
        
        // Get this week's financial data
        $totalIncome = Pendapatan::whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->where('status', 'approved')
            ->sum('jumlah');
            
        $totalExpense = Pengeluaran::whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->where('status', 'approved')
            ->sum('jumlah');

        $data = [
            'week_start' => $startOfWeek,
            'week_end' => $endOfWeek,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
        ];

        $this->dispatcher->dispatchWeeklyRecap($data);
        
        $this->info('Weekly recap sent successfully!');
        return 0;
    }
}
