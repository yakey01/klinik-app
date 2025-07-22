<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix nama_lengkap issues in production
     * Ensures username remains unchanged for login continuity
     */
    public function up()
    {
        // Fix Dr. Yaya's nama_lengkap
        $dokterYaya = DB::table('dokters')->where('username', 'yaya')->first();
        
        if ($dokterYaya) {
            // Update dokter nama_lengkap
            DB::table('dokters')
                ->where('id', $dokterYaya->id)
                ->update([
                    'nama_lengkap' => 'Dr. Yaya Mulyana, M.Kes',
                    'updated_at' => now()
                ]);
            
            // Update associated user name
            if ($dokterYaya->user_id) {
                DB::table('users')
                    ->where('id', $dokterYaya->user_id)
                    ->update([
                        'name' => 'Dr. Yaya Mulyana, M.Kes',
                        'updated_at' => now()
                    ]);
            }
            
            echo "✅ Fixed Dr. Yaya nama_lengkap to 'Dr. Yaya Mulyana, M.Kes'" . PHP_EOL;
            echo "✅ Username remains 'yaya' for login" . PHP_EOL;
        } else {
            echo "⚠️ Dr. Yaya not found by username 'yaya'" . PHP_EOL;
            
            // Try to find by nama_lengkap pattern
            $possibleYaya = DB::table('dokters')
                ->where('nama_lengkap', 'LIKE', '%yaya%')
                ->orWhere('nama_lengkap', 'LIKE', '%Yaya%')
                ->first();
            
            if ($possibleYaya) {
                echo "Found possible match: " . $possibleYaya->nama_lengkap . PHP_EOL;
                
                // Update nama_lengkap
                DB::table('dokters')
                    ->where('id', $possibleYaya->id)
                    ->update([
                        'nama_lengkap' => 'Dr. Yaya Mulyana, M.Kes',
                        'updated_at' => now()
                    ]);
                
                // Update user name
                if ($possibleYaya->user_id) {
                    DB::table('users')
                        ->where('id', $possibleYaya->user_id)
                        ->update([
                            'name' => 'Dr. Yaya Mulyana, M.Kes',
                            'updated_at' => now()
                        ]);
                }
                
                echo "✅ Fixed nama_lengkap for dokter ID: " . $possibleYaya->id . PHP_EOL;
            }
        }
        
        // Fix any other dokters where username was incorrectly set to nama_lengkap
        $problematicDokters = DB::table('dokters')
            ->whereRaw('username = nama_lengkap')
            ->get();
        
        foreach ($problematicDokters as $dokter) {
            // Generate proper username from nama_lengkap
            $username = strtolower(str_replace([' ', '.', ',', 'Dr', 'dr'], '', $dokter->nama_lengkap));
            $username = preg_replace('/[^a-z0-9]/', '', $username);
            $username = substr($username, 0, 20);
            
            // Ensure username is unique
            $counter = 1;
            $baseUsername = $username;
            while (DB::table('dokters')->where('username', $username)->where('id', '!=', $dokter->id)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }
            
            DB::table('dokters')
                ->where('id', $dokter->id)
                ->update([
                    'username' => $username,
                    'updated_at' => now()
                ]);
            
            echo "✅ Fixed username for dokter '{$dokter->nama_lengkap}' to '{$username}'" . PHP_EOL;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        // This migration is a data fix, not reversible
        echo "This migration fixes data and cannot be reversed" . PHP_EOL;
    }
};