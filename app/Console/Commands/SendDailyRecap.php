<?php

namespace App\Console\Commands;

use App\Services\NotificationDispatcher;
use App\Models\Pendapatan;
use App\Models\Pengeluaran;
use Illuminate\Console\Command;

class SendDailyRecap extends Command
{
    protected $signature = 'telegram:daily-recap';
    protected $description = 'Send daily recap notifications via Telegram';

    protected NotificationDispatcher $dispatcher;

    public function __construct(NotificationDispatcher $dispatcher)
    {
        parent::__construct();
        $this->dispatcher = $dispatcher;
    }

    public function handle()
    {
        $today = now()->format('Y-m-d');
        
        // Get today's financial data
        $totalIncome = Pendapatan::whereDate('created_at', $today)
            ->where('status', 'approved')
            ->sum('jumlah');
            
        $totalExpense = Pengeluaran::whereDate('created_at', $today)
            ->where('status', 'approved')
            ->sum('jumlah');

        $data = [
            'date' => $today,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,
        ];

        $this->dispatcher->dispatchDailyRecap($data);
        
        $this->info('Daily recap sent successfully!');
        return 0;
    }
}
