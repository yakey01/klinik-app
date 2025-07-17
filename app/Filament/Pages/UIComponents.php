<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class UIComponents extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-swatch';
    
    protected static string $view = 'filament.pages.ui-components';
    
    protected static ?string $navigationLabel = 'UI Components';
    
    protected static ?string $title = 'UI Component Library';
    
    protected static ?string $navigationGroup = '⚙️ SYSTEM ADMINISTRATION';
    
    protected static ?int $navigationSort = 4;
    
    protected static ?string $slug = 'ui-components';
    
    // Demo data
    public $demoStats = [];
    public $demoNotifications = [];
    public $demoProgress = [];
    
    public static function canAccess(): bool
    {
        return false; // Disabled due to custom component conflicts
    }
    
    public function mount(): void
    {
        $this->loadDemoData();
    }
    
    public function loadDemoData(): void
    {
        $this->demoStats = [
            [
                'title' => 'Total Users',
                'value' => '1,234',
                'change' => '+12%',
                'changeType' => 'increase',
                'icon' => 'heroicon-o-users',
                'color' => 'blue',
                'period' => 'vs last month',
            ],
            [
                'title' => 'Revenue',
                'value' => '$45,678',
                'change' => '+8%',
                'changeType' => 'increase',
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'green',
                'period' => 'vs last month',
            ],
            [
                'title' => 'Active Sessions',
                'value' => '567',
                'change' => '-3%',
                'changeType' => 'decrease',
                'icon' => 'heroicon-o-globe-alt',
                'color' => 'yellow',
                'period' => 'vs last hour',
            ],
            [
                'title' => 'System Health',
                'value' => '98.5%',
                'change' => '+0.2%',
                'changeType' => 'increase',
                'icon' => 'heroicon-o-heart',
                'color' => 'red',
                'period' => 'uptime',
            ],
        ];
        
        $this->demoNotifications = [
            [
                'type' => 'success',
                'title' => 'Success',
                'message' => 'Your operation completed successfully!',
            ],
            [
                'type' => 'warning',
                'title' => 'Warning',
                'message' => 'Please review the following items before proceeding.',
            ],
            [
                'type' => 'error',
                'title' => 'Error',
                'message' => 'Something went wrong. Please try again.',
            ],
            [
                'type' => 'info',
                'title' => 'Information',
                'message' => 'Here is some useful information for you.',
            ],
        ];
        
        $this->demoProgress = [
            ['label' => 'Project Alpha', 'value' => 75, 'color' => 'blue'],
            ['label' => 'Project Beta', 'value' => 45, 'color' => 'green'],
            ['label' => 'Project Gamma', 'value' => 90, 'color' => 'yellow'],
            ['label' => 'Project Delta', 'value' => 60, 'color' => 'red'],
        ];
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action('loadDemoData'),
        ];
    }
    
    public function showNotification(string $type): void
    {
        $messages = [
            'success' => 'This is a success notification!',
            'warning' => 'This is a warning notification!',
            'error' => 'This is an error notification!',
            'info' => 'This is an info notification!',
        ];
        
        \Filament\Notifications\Notification::make()
            ->title('Demo Notification')
            ->body($messages[$type] ?? 'This is a demo notification!')
            ->color($type === 'error' ? 'danger' : $type)
            ->send();
    }
    
    public function demoAction(): void
    {
        $this->showNotification('success');
    }
    
    public function demoLoadingAction(): void
    {
        // Simulate loading
        sleep(2);
        $this->showNotification('info');
    }
}