<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckDuplicateNips extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:check-duplicate-nips {--fix : Automatically fix duplicates by adding suffix}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for duplicate NIPs in users table and optionally fix them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for duplicate NIPs in users table...');
        
        // Find NIPs that have more than one user
        $duplicateNips = User::select('nip', DB::raw('COUNT(*) as count'))
            ->whereNotNull('nip')
            ->where('nip', '!=', '')
            ->groupBy('nip')
            ->having('count', '>', 1)
            ->get();
            
        if ($duplicateNips->isEmpty()) {
            $this->info('✅ No duplicate NIPs found in users table.');
            return 0;
        }
        
        $this->warn("⚠️  Found {$duplicateNips->count()} duplicate NIP(s):");
        
        foreach ($duplicateNips as $duplicate) {
            $users = User::where('nip', $duplicate->nip)->get();
            
            $this->line("NIP: {$duplicate->nip} (used by {$duplicate->count} users):");
            
            foreach ($users as $user) {
                $this->line("  - ID: {$user->id}, Name: {$user->name}, Username: {$user->username}, Email: {$user->email}");
            }
            
            if ($this->option('fix')) {
                $this->fixDuplicateNip($users);
            }
            
            $this->line('');
        }
        
        if (!$this->option('fix')) {
            $this->info('To automatically fix duplicates, run with --fix option');
            $this->warn('⚠️  Manual review is recommended before fixing duplicates');
        }
        
        return 0;
    }
    
    /**
     * Fix duplicate NIPs by adding suffix to newer records
     */
    private function fixDuplicateNip($users)
    {
        // Sort by creation date - keep the oldest one unchanged
        $sortedUsers = $users->sortBy('created_at');
        $originalUser = $sortedUsers->first();
        $duplicateUsers = $sortedUsers->skip(1);
        
        $this->line("  Keeping original NIP for: {$originalUser->name} (ID: {$originalUser->id})");
        
        foreach ($duplicateUsers as $index => $user) {
            $newNip = $user->nip . '_' . ($index + 1);
            
            // Ensure the new NIP is also unique
            while (User::where('nip', $newNip)->exists()) {
                $newNip = $user->nip . '_' . (time() + $index);
            }
            
            $user->update(['nip' => $newNip]);
            $this->line("  Updated {$user->name} (ID: {$user->id}) NIP to: {$newNip}");
        }
    }
}