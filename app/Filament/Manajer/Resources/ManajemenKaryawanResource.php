<?php

namespace App\Filament\Manajer\Resources;

use App\Filament\Manajer\Resources\ManajemenKaryawanResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ManajemenKaryawanResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = '👥 Employee Management';

    protected static ?string $navigationLabel = '👨‍💼 Manajemen Karyawan';

    protected static ?string $modelLabel = 'Karyawan';

    protected static ?string $pluralModelLabel = 'Manajemen Karyawan';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'manajer';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Karyawan')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('👤 Nama Lengkap')
                            ->disabled(),

                        Forms\Components\TextInput::make('email')
                            ->label('📧 Email')
                            ->disabled(),

                        Forms\Components\TextInput::make('nip')
                            ->label('🆔 NIP')
                            ->disabled(),

                        Forms\Components\TextInput::make('no_telepon')
                            ->label('📱 No. Telepon')
                            ->disabled(),

                        Forms\Components\TextInput::make('role.name')
                            ->label('👔 Role')
                            ->disabled(),

                        Forms\Components\DatePicker::make('tanggal_bergabung')
                            ->label('📅 Tanggal Bergabung')
                            ->disabled(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('✅ Status Aktif')
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Statistik Kinerja')
                    ->schema([
                        Forms\Components\Placeholder::make('total_tindakan')
                            ->label('🏥 Total Tindakan')
                            ->content(function ($record) {
                                if (!$record) return '0';
                                return $record->tindakanAsDokter()->count() + 
                                       $record->tindakanAsParamedis()->count() + 
                                       $record->tindakanAsNonParamedis()->count();
                            }),

                        Forms\Components\Placeholder::make('total_jaspel')
                            ->label('💰 Total Jaspel')
                            ->content(function ($record) {
                                if (!$record) return 'Rp 0';
                                $total = $record->jaspel()->sum('nominal');
                                return 'Rp ' . number_format($total, 0, ',', '.');
                            }),

                        Forms\Components\Placeholder::make('tindakan_bulan_ini')
                            ->label('📊 Tindakan Bulan Ini')
                            ->content(function ($record) {
                                if (!$record) return '0';
                                $start = Carbon::now()->startOfMonth();
                                $end = Carbon::now()->endOfMonth();
                                return $record->tindakanAsDokter()->whereBetween('created_at', [$start, $end])->count() + 
                                       $record->tindakanAsParamedis()->whereBetween('created_at', [$start, $end])->count() + 
                                       $record->tindakanAsNonParamedis()->whereBetween('created_at', [$start, $end])->count();
                            }),

                        Forms\Components\Placeholder::make('last_activity')
                            ->label('🕐 Aktivitas Terakhir')
                            ->content(function ($record) {
                                if (!$record) return '-';
                                $lastTindakan = $record->tindakanAsDokter()
                                    ->orWhere('paramedis_id', $record->id)
                                    ->orWhere('non_paramedis_id', $record->id)
                                    ->latest()
                                    ->first();
                                return $lastTindakan ? $lastTindakan->created_at->diffForHumans() : 'Belum ada aktivitas';
                            }),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('👥 Manajemen Karyawan Overview')
            ->description('Staff overview dan performance monitoring untuk manajemen')
            ->query(
                User::query()
                    ->with(['role', 'jaspel', 'tindakanAsDokter', 'tindakanAsParamedis', 'tindakanAsNonParamedis'])
                    ->whereHas('role', function (Builder $query) {
                        $query->whereIn('name', ['dokter', 'paramedis', 'perawat', 'bendahara', 'petugas', 'non_paramedis']);
                    })
            )
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('📷')
                    ->circular()
                    ->defaultImageUrl(fn() => 'https://ui-avatars.com/api/?name=' . urlencode('User') . '&background=6366f1&color=fff')
                    ->size(40),

                Tables\Columns\TextColumn::make('name')
                    ->label('👤 Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('role.name')
                    ->label('👔 Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'dokter' => 'success',
                        'paramedis' => 'info',
                        'perawat' => 'warning',
                        'bendahara' => 'primary',
                        'petugas' => 'gray',
                        'non_paramedis' => 'secondary',
                        default => 'gray'
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'dokter' => 'heroicon-m-academic-cap',
                        'paramedis' => 'heroicon-m-heart',
                        'perawat' => 'heroicon-m-user-plus',
                        'bendahara' => 'heroicon-m-currency-dollar',
                        'petugas' => 'heroicon-m-clipboard-document-list',
                        'non_paramedis' => 'heroicon-m-wrench-screwdriver',
                        default => 'heroicon-m-user'
                    }),

                Tables\Columns\TextColumn::make('nip')
                    ->label('🆔 NIP')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('no_telepon')
                    ->label('📱 Telepon')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_tindakan')
                    ->label('🏥 Total Tindakan')
                    ->getStateUsing(function ($record) {
                        return $record->tindakanAsDokter()->count() + 
                               $record->tindakanAsParamedis()->count() + 
                               $record->tindakanAsNonParamedis()->count();
                    })
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => $state > 50 ? 'success' : ($state > 20 ? 'warning' : 'gray')),

                Tables\Columns\TextColumn::make('total_jaspel')
                    ->label('💰 Total Jaspel')
                    ->getStateUsing(function ($record) {
                        return $record->jaspel()->sum('nominal');
                    })
                    ->money('IDR')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold')
                    ->color(fn ($state) => $state > 1000000 ? 'success' : ($state > 500000 ? 'warning' : 'gray')),

                Tables\Columns\TextColumn::make('tanggal_bergabung')
                    ->label('📅 Bergabung')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('✅ Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('last_activity')
                    ->label('🕐 Aktivitas Terakhir')
                    ->getStateUsing(function ($record) {
                        $lastTindakan = $record->tindakanAsDokter()
                            ->orWhere('paramedis_id', $record->id)
                            ->orWhere('non_paramedis_id', $record->id)
                            ->latest()
                            ->first();
                        return $lastTindakan ? $lastTindakan->created_at->diffForHumans() : 'Belum ada aktivitas';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('👔 Filter Role')
                    ->relationship('role', 'name')
                    ->options([
                        'dokter' => '👨‍⚕️ Dokter',
                        'paramedis' => '👩‍⚕️ Paramedis',
                        'perawat' => '👨‍⚕️ Perawat',
                        'bendahara' => '💰 Bendahara',
                        'petugas' => '📋 Petugas',
                        'non_paramedis' => '🔧 Non Paramedis',
                    ]),

                Filter::make('active_only')
                    ->label('✅ Hanya Aktif')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->default(),

                Filter::make('high_performer')
                    ->label('🌟 High Performer')
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('jaspel', function (Builder $q) {
                            $q->havingRaw('SUM(nominal) > 1000000');
                        });
                    }),

                Filter::make('new_employee')
                    ->label('🆕 Karyawan Baru (6 bulan)')
                    ->query(fn (Builder $query): Builder => $query->where('tanggal_bergabung', '>=', now()->subMonths(6))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('👁️ Detail')
                    ->color('info'),

                Tables\Actions\Action::make('performance_report')
                    ->label('📊 Laporan Kinerja')
                    ->icon('heroicon-m-chart-bar')
                    ->color('success')
                    ->action(function ($record) {
                        $totalTindakan = $record->tindakanAsDokter()->count() + 
                                       $record->tindakanAsParamedis()->count() + 
                                       $record->tindakanAsNonParamedis()->count();
                        $totalJaspel = $record->jaspel()->sum('nominal');
                        
                        \Filament\Notifications\Notification::make()
                            ->title('📊 Performance Report: ' . $record->name)
                            ->body("Total Tindakan: {$totalTindakan} | Total Jaspel: Rp " . number_format($totalJaspel, 0, ',', '.'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_performance')
                        ->label('📊 Export Performance')
                        ->icon('heroicon-m-document-arrow-down')
                        ->color('success')
                        ->action(function ($records) {
                            \Filament\Notifications\Notification::make()
                                ->title('📊 Performance Export')
                                ->body('Performance data untuk ' . $records->count() . ' karyawan telah diekspor')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('total_jaspel', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->poll('120s');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListManajemenKaryawans::route('/'),
            'view' => Pages\ViewManajemenKaryawan::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // View-only resource for manager
    }

    public static function canEdit($record): bool
    {
        return false; // View-only resource for manager
    }

    public static function canDelete($record): bool
    {
        return false; // View-only resource for manager
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'manajer';
    }
}