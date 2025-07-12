<?php

namespace App\Filament\Resources\UserManagementResource\Pages;

use App\Filament\Resources\UserManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserManagement extends EditRecord
{
    protected static string $resource = UserManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_activity')
                ->label('View Activity Log')
                ->icon('heroicon-o-clock')
                ->color('info')
                ->url(fn () => route('filament.admin.resources.users.activity', $this->record)),

            Actions\Action::make('send_welcome_email')
                ->label('Send Welcome Email')
                ->icon('heroicon-o-envelope')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    // Implement welcome email logic
                    \Filament\Notifications\Notification::make()
                        ->title('Email Sent')
                        ->body("Welcome email sent to {$this->record->email}")
                        ->success()
                        ->send();
                }),

            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        \Filament\Notifications\Notification::make()
            ->title('User Updated')
            ->body("User '{$this->record->name}' has been updated successfully.")
            ->success()
            ->send();
    }
}