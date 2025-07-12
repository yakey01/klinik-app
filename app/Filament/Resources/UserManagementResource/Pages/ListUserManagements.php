<?php

namespace App\Filament\Resources\UserManagementResource\Pages;

use App\Filament\Resources\UserManagementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUserManagements extends ListRecords
{
    protected static string $resource = UserManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('system_info')
                ->label('📊 System Info')
                ->icon('heroicon-o-information-circle')
                ->color('info')
                ->modal()
                ->modalHeading('System Information')
                ->modalContent(view('filament.pages.system-info')),

            Actions\Action::make('bulk_invite')
                ->label('📧 Bulk Invite')
                ->icon('heroicon-o-envelope')
                ->color('success')
                ->modal()
                ->modalHeading('Bulk User Invitation')
                ->modalDescription('Send invitation emails to multiple users at once')
                ->form([
                    \Filament\Forms\Components\Textarea::make('emails')
                        ->label('Email Addresses')
                        ->placeholder('Enter email addresses, one per line')
                        ->rows(5)
                        ->required(),
                    \Filament\Forms\Components\Select::make('default_role')
                        ->label('Default Role')
                        ->relationship('role', 'display_name')
                        ->required(),
                ])
                ->action(function (array $data) {
                    // Implement bulk invitation logic
                    \Filament\Notifications\Notification::make()
                        ->title('Feature Coming Soon')
                        ->body('Bulk user invitation will be available in the next update.')
                        ->info()
                        ->send();
                }),

            Actions\CreateAction::make()
                ->label('Add New User')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Users')
                ->badge(\App\Models\User::count()),

            'active' => Tab::make('Active')
                ->badge(\App\Models\User::where('is_active', true)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),

            'inactive' => Tab::make('Inactive')
                ->badge(\App\Models\User::where('is_active', false)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),

            'admins' => Tab::make('Admins')
                ->badge(\App\Models\User::whereHas('role', fn ($q) => $q->where('name', 'admin'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->where('name', 'admin'))),

            'doctors' => Tab::make('Doctors')
                ->badge(\App\Models\User::whereHas('role', fn ($q) => $q->where('name', 'dokter'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->where('name', 'dokter'))),

            'staff' => Tab::make('Staff')
                ->badge(\App\Models\User::whereHas('role', fn ($q) => $q->whereIn('name', ['petugas', 'paramedis', 'bendahara']))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->whereIn('name', ['petugas', 'paramedis', 'bendahara']))),
        ];
    }
}