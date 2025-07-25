<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Artisan;

class MaintenanceMode extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    
    protected static string $view = 'filament.pages.maintenance-mode';
    
    protected static ?string $navigationLabel = 'Maintenance Mode';
    
    protected static ?string $title = 'Maintenance Mode Control';
    
    protected static ?string $navigationGroup = '⚙️ SYSTEM ADMINISTRATION';
    
    protected static ?int $navigationSort = 3;

    public ?array $data = [];

    public function mount(): void
    {
        $this->data = [
            'maintenance_mode' => SystemSetting::get('maintenance_mode', false),
            'maintenance_message' => SystemSetting::get('maintenance_message', 'System is under maintenance. Please try again later.'),
            'maintenance_start' => SystemSetting::get('maintenance_start', null),
            'maintenance_end' => SystemSetting::get('maintenance_end', null),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Maintenance Mode Status')
                    ->schema([
                        Toggle::make('maintenance_mode')
                            ->label('Enable Maintenance Mode')
                            ->helperText('When enabled, only administrators can access the system')
                            ->reactive(),
                        
                        Grid::make(2)
                            ->schema([
                                DateTimePicker::make('maintenance_start')
                                    ->label('Start Time')
                                    ->helperText('When maintenance mode should automatically start')
                                    ->visible(fn ($get) => $get('maintenance_mode')),
                                
                                DateTimePicker::make('maintenance_end')
                                    ->label('End Time')
                                    ->helperText('When maintenance mode should automatically end')
                                    ->visible(fn ($get) => $get('maintenance_mode')),
                            ]),
                    ]),
                
                Section::make('Maintenance Configuration')
                    ->schema([
                        Textarea::make('maintenance_message')
                            ->label('Maintenance Message')
                            ->helperText('Message to display to users during maintenance')
                            ->rows(3)
                            ->visible(fn ($get) => $get('maintenance_mode')),
                    ]),
            ])
            ->statePath('data');
    }

    public function getActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->icon('heroicon-o-check')
                ->color('success')
                ->action('save'),
            
            Action::make('laravel_down')
                ->label('Laravel Down')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->action('laravelDown')
                ->visible(fn () => !app()->isDownForMaintenance())
                ->requiresConfirmation()
                ->modalHeading('Enable Laravel Maintenance Mode')
                ->modalDescription('This will put the entire application in maintenance mode using Laravel\'s built-in maintenance mode.'),
            
            Action::make('laravel_up')
                ->label('Laravel Up')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action('laravelUp')
                ->visible(fn () => app()->isDownForMaintenance()),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        // Save settings
        SystemSetting::set('maintenance_mode', $data['maintenance_mode'], 'boolean');
        SystemSetting::set('maintenance_message', $data['maintenance_message'], 'string');
        SystemSetting::set('maintenance_start', $data['maintenance_start'], 'string');
        SystemSetting::set('maintenance_end', $data['maintenance_end'], 'string');
        
        Notification::make()
            ->title('Maintenance Settings Saved')
            ->success()
            ->send();
    }

    public function laravelDown(): void
    {
        $message = $this->data['maintenance_message'] ?? 'System is under maintenance. Please try again later.';
        
        Artisan::call('down', [
            '--message' => $message,
            '--render' => 'errors::503',
        ]);
        
        Notification::make()
            ->title('Laravel Maintenance Mode Enabled')
            ->body('The application has been put into maintenance mode')
            ->warning()
            ->send();
    }

    public function laravelUp(): void
    {
        Artisan::call('up');
        
        Notification::make()
            ->title('Laravel Maintenance Mode Disabled')
            ->body('The application is now accessible to all users')
            ->success()
            ->send();
    }

    public function getCurrentMaintenanceStatus(): array
    {
        return [
            'laravel_down' => app()->isDownForMaintenance(),
            'custom_maintenance' => SystemSetting::get('maintenance_mode', false),
            'message' => SystemSetting::get('maintenance_message', 'System is under maintenance. Please try again later.'),
        ];
    }
}
