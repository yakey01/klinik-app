<?php

namespace App\Filament\Dokter\Widgets;

use Filament\Widgets\Widget;
use App\Models\Dokter;
use Illuminate\Support\Facades\Auth;

class WelcomeWidget extends Widget
{
    protected static string $view = 'filament.dokter.widgets.welcome-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    protected function getViewData(): array
    {
        $user = Auth::user();
        $dokter = Dokter::where('user_id', $user->id)->first();
        
        $greeting = $this->getTimeBasedGreeting();
        
        return [
            'user' => $user,
            'dokter' => $dokter,
            'greeting' => $greeting,
        ];
    }
    
    private function getTimeBasedGreeting(): string
    {
        $hour = now()->hour;
        
        if ($hour < 12) {
            return 'Selamat Pagi';
        } elseif ($hour < 15) {
            return 'Selamat Siang';
        } elseif ($hour < 18) {
            return 'Selamat Sore';
        } else {
            return 'Selamat Malam';
        }
    }
}