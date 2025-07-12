<?php

namespace App\Filament\Manajer\Resources;

use App\Filament\Manajer\Resources\LaporanKeuanganResource\Pages;
use App\Models\PendapatanHarian;
use App\Models\PengeluaranHarian;
use App\Models\Tindakan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class LaporanKeuanganResource extends Resource
{
    protected static ?string $model = PendapatanHarian::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = '💰 Financial Reports';

    protected static ?string $navigationLabel = '📊 Laporan Keuangan';

    protected static ?string $modelLabel = 'Laporan Keuangan';

    protected static ?string $pluralModelLabel = 'Laporan Keuangan';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'manajer';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Read-only form for viewing financial reports
                Forms\Components\Section::make('Filter Laporan')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->default(now()->startOfMonth()),
                            
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Tanggal Akhir')
                            ->default(now()->endOfMonth()),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('💰 Laporan Keuangan Komprehensif')
            ->description('View-only financial reports untuk manajemen analisis')
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_input')
                    ->label('📅 Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('kategori')
                    ->label('📂 Kategori')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'konsultasi' => 'info',
                        'tindakan_medis' => 'success',
                        'obat' => 'warning',
                        'laboratorium' => 'primary',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('sumber')
                    ->label('💼 Sumber Pendapatan')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('nominal')
                    ->label('💰 Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\BadgeColumn::make('status_validasi')
                    ->label('✅ Status')
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'disetujui' => 'success',
                        'ditolak' => 'danger',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        default => ucfirst($state)
                    }),

                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('👤 Input Oleh')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('validatedBy.name')
                    ->label('✍️ Validasi Oleh')
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('🕐 Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('status_disetujui')
                    ->label('✅ Hanya Disetujui')
                    ->query(fn (Builder $query): Builder => $query->where('status_validasi', 'disetujui'))
                    ->default(),

                Tables\Filters\SelectFilter::make('kategori')
                    ->label('📂 Kategori')
                    ->options([
                        'konsultasi' => 'Konsultasi',
                        'tindakan_medis' => 'Tindakan Medis',
                        'obat' => 'Obat',
                        'laboratorium' => 'Laboratorium',
                    ]),

                Tables\Filters\SelectFilter::make('status_validasi')
                    ->label('✅ Status Validasi')
                    ->options([
                        'pending' => 'Menunggu',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ]),

                Filter::make('tanggal_range')
                    ->form([
                        Forms\Components\DatePicker::make('dari')
                            ->label('📅 Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('📅 Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_input', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_input', '<=', $date),
                            );
                    }),

                Filter::make('bulan_ini')
                    ->label('📆 Bulan Ini')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('tanggal_input', [now()->startOfMonth(), now()->endOfMonth()])),

                Filter::make('hari_ini')
                    ->label('📅 Hari Ini')
                    ->query(fn (Builder $query): Builder => $query->whereDate('tanggal_input', today())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('👁️ Lihat')
                    ->color('info'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export_summary')
                        ->label('📊 Export Summary')
                        ->icon('heroicon-m-document-arrow-down')
                        ->color('success')
                        ->action(function ($records) {
                            $total = $records->sum('nominal');
                            \Filament\Notifications\Notification::make()
                                ->title('📊 Summary Generated')
                                ->body("Total: Rp " . number_format($total, 0, ',', '.'))
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('tanggal_input', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->poll('60s');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status_validasi', 'pending')->count();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLaporanKeuangans::route('/'),
            'view' => Pages\ViewLaporanKeuangan::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // View-only resource
    }

    public static function canEdit($record): bool
    {
        return false; // View-only resource
    }

    public static function canDelete($record): bool
    {
        return false; // View-only resource
    }

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'manajer';
    }
}