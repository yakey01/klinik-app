<?php

namespace App\Console\Commands;

use App\Services\AutoCodeGeneratorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestAutoCodeGeneration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:auto-code-generation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test auto code generation for Pendapatan and Pengeluaran';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Auto Code Generation...');
        
        // Test Pendapatan code generation
        $this->info('Testing Pendapatan Code Generation:');
        for ($i = 1; $i <= 5; $i++) {
            try {
                $code = AutoCodeGeneratorService::generatePendapatanCode();
                $this->info("  Generated: {$code}");
            } catch (\Exception $e) {
                $this->error("  Error: " . $e->getMessage());
            }
        }
        
        $this->info('');
        
        // Test Pengeluaran code generation
        $this->info('Testing Pengeluaran Code Generation:');
        for ($i = 1; $i <= 5; $i++) {
            try {
                $code = AutoCodeGeneratorService::generatePengeluaranCode();
                $this->info("  Generated: {$code}");
            } catch (\Exception $e) {
                $this->error("  Error: " . $e->getMessage());
            }
        }
        
        $this->info('');
        
        // Test validation
        $this->info('Testing Code Format Validation:');
        $testCodes = [
            'PND-0001' => 'PND',
            'PND-1234' => 'PND',
            'PND-123' => 'PND', // Should fail
            'PNG-0001' => 'PNG',
            'PNG-9999' => 'PNG',
            'INVALID' => 'PND', // Should fail
        ];
        
        foreach ($testCodes as $code => $prefix) {
            $isValid = AutoCodeGeneratorService::validateCodeFormat($code, $prefix);
            $status = $isValid ? '✓ Valid' : '✗ Invalid';
            $this->info("  {$code} => {$status}");
        }
        
        $this->info('');
        $this->info('✅ Test completed!');
        
        return 0;
    }
}