<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDuplicateUsers extends Command
{
    protected $signature = 'user:check-duplicates {--fix : Fix duplicate issues automatically}';
    protected $description = 'Check for duplicate user data (NIP, email, username) and optionally fix them';

    public function handle()
    {
        $this->info('🔍 Checking for duplicate user data...');
        
        $duplicates = [];
        
        // Check duplicate NIPs
        $this->line('');
        $this->info('📋 Checking duplicate NIPs...');
        $nipDuplicates = User::whereNotNull('nip')
            ->select('nip', DB::raw('count(*) as count'), DB::raw('GROUP_CONCAT(id) as user_ids'), DB::raw('GROUP_CONCAT(name) as names'))
            ->groupBy('nip')
            ->having('count', '>', 1)
            ->get();
            
        if ($nipDuplicates->count() > 0) {
            $this->warn("⚠️  Found {$nipDuplicates->count()} duplicate NIP(s):");
            foreach ($nipDuplicates as $dup) {
                $this->line("   NIP: {$dup->nip} - Users: {$dup->names} (IDs: {$dup->user_ids})");
                $duplicates['nip'][] = $dup;
            }
        } else {
            $this->info('✅ No duplicate NIPs found');
        }
        
        // Check duplicate emails
        $this->line('');
        $this->info('📧 Checking duplicate emails...');
        $emailDuplicates = User::whereNotNull('email')
            ->select('email', DB::raw('count(*) as count'), DB::raw('GROUP_CONCAT(id) as user_ids'), DB::raw('GROUP_CONCAT(name) as names'))
            ->groupBy('email')
            ->having('count', '>', 1)
            ->get();
            
        if ($emailDuplicates->count() > 0) {
            $this->warn("⚠️  Found {$emailDuplicates->count()} duplicate email(s):");
            foreach ($emailDuplicates as $dup) {
                $this->line("   Email: {$dup->email} - Users: {$dup->names} (IDs: {$dup->user_ids})");
                $duplicates['email'][] = $dup;
            }
        } else {
            $this->info('✅ No duplicate emails found');
        }
        
        // Check duplicate usernames
        $this->line('');
        $this->info('👤 Checking duplicate usernames...');
        $usernameDuplicates = User::whereNotNull('username')
            ->select('username', DB::raw('count(*) as count'), DB::raw('GROUP_CONCAT(id) as user_ids'), DB::raw('GROUP_CONCAT(name) as names'))
            ->groupBy('username')
            ->having('count', '>', 1)
            ->get();
            
        if ($usernameDuplicates->count() > 0) {
            $this->warn("⚠️  Found {$usernameDuplicates->count()} duplicate username(s):");
            foreach ($usernameDuplicates as $dup) {
                $this->line("   Username: {$dup->username} - Users: {$dup->names} (IDs: {$dup->user_ids})");
                $duplicates['username'][] = $dup;
            }
        } else {
            $this->info('✅ No duplicate usernames found');
        }
        
        // Summary
        $this->line('');
        $totalDuplicates = collect($duplicates)->flatten()->count();
        
        if ($totalDuplicates === 0) {
            $this->info('🎉 No duplicate user data found! Database is clean.');
            return;
        }
        
        $this->warn("📊 Summary: Found {$totalDuplicates} duplicate issue(s)");
        
        // Fix duplicates if requested
        if ($this->option('fix')) {
            $this->line('');
            $this->info('🔧 Fixing duplicate issues...');
            
            $fixed = 0;
            
            // Fix NIP duplicates
            if (isset($duplicates['nip'])) {
                foreach ($duplicates['nip'] as $dup) {
                    $userIds = explode(',', $dup->user_ids);
                    // Keep first user, modify others
                    for ($i = 1; $i < count($userIds); $i++) {
                        $user = User::find($userIds[$i]);
                        if ($user) {
                            $newNip = $user->nip . '_dup' . $i;
                            $user->update(['nip' => $newNip]);
                            $this->line("   ✓ Updated User ID {$user->id} NIP from '{$dup->nip}' to '{$newNip}'");
                            $fixed++;
                        }
                    }
                }
            }
            
            // Fix email duplicates
            if (isset($duplicates['email'])) {
                foreach ($duplicates['email'] as $dup) {
                    $userIds = explode(',', $dup->user_ids);
                    // Keep first user, modify others
                    for ($i = 1; $i < count($userIds); $i++) {
                        $user = User::find($userIds[$i]);
                        if ($user) {
                            $emailParts = explode('@', $user->email);
                            $newEmail = $emailParts[0] . '_dup' . $i . '@' . $emailParts[1];
                            $user->update(['email' => $newEmail]);
                            $this->line("   ✓ Updated User ID {$user->id} email from '{$dup->email}' to '{$newEmail}'");
                            $fixed++;
                        }
                    }
                }
            }
            
            // Fix username duplicates
            if (isset($duplicates['username'])) {
                foreach ($duplicates['username'] as $dup) {
                    $userIds = explode(',', $dup->user_ids);
                    // Keep first user, modify others
                    for ($i = 1; $i < count($userIds); $i++) {
                        $user = User::find($userIds[$i]);
                        if ($user) {
                            $newUsername = $user->username . '_dup' . $i;
                            $user->update(['username' => $newUsername]);
                            $this->line("   ✓ Updated User ID {$user->id} username from '{$dup->username}' to '{$newUsername}'");
                            $fixed++;
                        }
                    }
                }
            }
            
            $this->info("🎉 Fixed {$fixed} duplicate issue(s)!");
        } else {
            $this->line('');
            $this->comment('💡 Run with --fix flag to automatically resolve duplicates:');
            $this->comment('   php artisan user:check-duplicates --fix');
        }
    }
}