<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ShiftTemplate;

return new class extends Migration
{
    public function up(): void
    {
        // Clear existing shift templates
        ShiftTemplate::truncate();
        
        // Insert new shift templates
        ShiftTemplate::create([
            'nama_shift' => 'Pagi',
            'jam_masuk' => '06:00:00',
            'jam_pulang' => '12:00:00',
        ]);
        
        ShiftTemplate::create([
            'nama_shift' => 'Sore',
            'jam_masuk' => '16:00:00',
            'jam_pulang' => '21:00:00',
        ]);
    }

    public function down(): void
    {
        // Restore original shifts if needed
        ShiftTemplate::truncate();
        
        ShiftTemplate::create([
            'nama_shift' => 'Pagi',
            'jam_masuk' => '07:00:00',
            'jam_pulang' => '15:00:00',
        ]);
        
        ShiftTemplate::create([
            'nama_shift' => 'Siang',
            'jam_masuk' => '15:00:00',
            'jam_pulang' => '23:00:00',
        ]);
        
        ShiftTemplate::create([
            'nama_shift' => 'Malam',
            'jam_masuk' => '23:00:00',
            'jam_pulang' => '07:00:00',
        ]);
    }
};