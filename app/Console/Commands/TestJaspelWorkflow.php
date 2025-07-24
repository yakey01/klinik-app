<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tindakan;
use App\Models\User;
use App\Models\Jaspel;
use App\Services\JaspelCalculationService;

class TestJaspelWorkflow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:jaspel-workflow {--user-id= : Test specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the complete Jaspel workflow from validation to mobile app display';

    protected $jaspelService;

    public function __construct(JaspelCalculationService $jaspelService)
    {
        parent::__construct();
        $this->jaspelService = $jaspelService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Jaspel Workflow...');
        $this->newLine();

        $userId = $this->option('user-id');
        
        // Step 1: Check if we have any pending tindakan to validate
        $this->info('📋 Step 1: Checking pending tindakan...');
        $pendingTindakan = Tindakan::where('status_validasi', 'pending')
            ->with(['dokter', 'paramedis', 'jenisTindakan', 'pasien'])
            ->limit(5)
            ->get();
            
        if ($pendingTindakan->isEmpty()) {
            $this->warn('⚠️  No pending tindakan found. Please create some tindakan first.');
            return;
        }
        
        $this->info("Found {$pendingTindakan->count()} pending tindakan");
        $this->newLine();

        // Step 2: Simulate validation process
        $this->info('✅ Step 2: Simulating validation process...');
        $approvedCount = 0;
        
        foreach ($pendingTindakan as $tindakan) {
            // Approve some tindakan
            if ($approvedCount < 3) {
                $tindakan->update([
                    'status_validasi' => 'approved',
                    'validated_by' => 1, // Assuming admin user ID 1
                    'validated_at' => now(),
                    'komentar_validasi' => 'Approved for testing'
                ]);
                
                $this->info("✓ Approved tindakan ID: {$tindakan->id}");
                $approvedCount++;
            }
        }
        $this->newLine();

        // Step 3: Calculate Jaspel from approved tindakan
        $this->info('💰 Step 3: Calculating Jaspel from approved tindakan...');
        $createdJaspel = $this->jaspelService->bulkCalculateFromValidatedTindakan();
        
        if (empty($createdJaspel)) {
            $this->warn('⚠️  No Jaspel records were created');
        } else {
            $this->info("✓ Created " . count($createdJaspel) . " Jaspel records");
            
            foreach ($createdJaspel as $jaspel) {
                $user = $jaspel->user;
                $this->line("  - User: {$user->name} | Amount: Rp " . number_format($jaspel->nominal, 0, ',', '.'));
            }
        }
        $this->newLine();

        // Step 4: Test API endpoint
        $this->info('🔌 Step 4: Testing API endpoint...');
        
        // Get a paramedis user for testing
        $paramedicUser = User::whereHas('roles', function($query) {
            $query->where('name', 'paramedis');
        })->first();
        
        if (!$paramedicUser) {
            $this->warn('⚠️  No paramedis user found for API testing');
        } else {
            $jaspelRecords = Jaspel::where('user_id', $paramedicUser->id)
                ->with(['tindakan.jenisTindakan', 'tindakan.pasien'])
                ->get();
                
            $this->info("✓ Found {$jaspelRecords->count()} Jaspel records for user: {$paramedicUser->name}");
            
            if ($jaspelRecords->isNotEmpty()) {
                $totalPaid = $jaspelRecords->where('status_validasi', 'disetujui')->sum('nominal');
                $totalPending = $jaspelRecords->where('status_validasi', 'pending')->sum('nominal');
                
                $this->line("  - Total Paid: Rp " . number_format($totalPaid, 0, ',', '.'));
                $this->line("  - Total Pending: Rp " . number_format($totalPending, 0, ',', '.'));
            }
        }
        $this->newLine();

        // Step 5: Test specific user if provided
        if ($userId) {
            $this->info("👤 Step 5: Testing specific user ID: {$userId}");
            $testUser = User::find($userId);
            
            if (!$testUser) {
                $this->error("❌ User with ID {$userId} not found");
                return;
            }
            
            $userJaspel = Jaspel::where('user_id', $userId)
                ->with(['tindakan.jenisTindakan'])
                ->get();
                
            $this->info("✓ Found {$userJaspel->count()} Jaspel records for {$testUser->name}");
            
            foreach ($userJaspel as $jaspel) {
                $tindakanName = $jaspel->tindakan ? $jaspel->tindakan->jenisTindakan->nama : 'Unknown';
                $this->line("  - {$tindakanName}: Rp " . number_format($jaspel->nominal, 0, ',', '.') . " ({$jaspel->status_validasi})");
            }
        }

        $this->newLine();
        $this->info('✅ Jaspel workflow test completed!');
        $this->info('🔍 Summary:');
        $this->line("  - Approved tindakan: {$approvedCount}");
        $this->line("  - Created Jaspel records: " . count($createdJaspel));
        $this->info('📱 You can now test the mobile app at: /paramedis/mobile-app');
    }
}