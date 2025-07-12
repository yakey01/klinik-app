<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PengeluaranResource\Pages;
use App\Filament\Resources\PengeluaranResource\Pages\ListPengeluaran;
use App\Filament\Resources\PengeluaranResource\Pages\CreatePengeluaran;
use App\Filament\Resources\PengeluaranResource\Pages\ViewPengeluaran;
use App\Filament\Resources\PengeluaranResource\Pages\EditPengeluaran;
use App\Models\Pengeluaran;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Colors\Color;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PengeluaranResource extends Resource
{
    protected static ?string $model = Pengeluaran::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';
    
    protected static ?string $navigationGroup = 'Keuangan';
    
    protected static ?string $modelLabel = 'Pengeluaran';
    
    protected static ?string $pluralModelLabel = 'Pengeluaran';
    
    protected static ?string $navigationBadgeTooltip = 'Jumlah pengeluaran pending';
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status_validasi', 'pending')->count() ?: null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status_validasi', 'pending')->count() > 0 ? 'warning' : 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\TextInput::make('kode_pengeluaran')
                            ->label('Kode Pengeluaran')
                            ->required()
                            ->maxLength(20)
                            ->placeholder('Masukkan kode pengeluaran')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->autofocus()
                            ->unique(ignoreRecord: true)
                            ->alphaDash()
                            ->helperText('Kode harus unik dan hanya boleh berisi huruf, angka, tanda hubung, dan underscore'),
                        
                        Forms\Components\TextInput::make('nama_pengeluaran')
                            ->label('Nama Pengeluaran')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Masukkan nama pengeluaran')
                            ->prefixIcon('heroicon-o-document-text')
                            ->helperText('Nama pengeluaran yang jelas dan deskriptif'),
                    ])
                    ->columnSpan('full')
                    ->extraAttributes([
                        'class' => 'gap-6',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_pengeluaran')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->icon('heroicon-o-hashtag'),
                
                Tables\Columns\TextColumn::make('nama_pengeluaran')
                    ->label('Nama Pengeluaran')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->nama_pengeluaran),
                
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->icon('heroicon-o-calendar'),
                
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => $record->keterangan),
                
                Tables\Columns\TextColumn::make('nominal')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->color('danger')
                    ->summarize([
                        Tables\Columns\Summarizers\Sum::make()
                            ->money('IDR')
                            ->label('Total'),
                    ]),
                
                Tables\Columns\BadgeColumn::make('kategori')
                    ->label('Kategori')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'operasional' => 'Operasional',
                        'gaji_karyawan' => 'Gaji Karyawan',
                        'alat_medis' => 'Alat Medis',
                        'obat_obatan' => 'Obat-obatan',
                        'listrik_air' => 'Listrik & Air',
                        'sewa_tempat' => 'Sewa Tempat',
                        'maintenance' => 'Maintenance',
                        'marketing' => 'Marketing',
                        'training' => 'Training',
                        'administrasi' => 'Administrasi',
                        'transportasi' => 'Transportasi',
                        'konsumsi' => 'Konsumsi',
                        'pajak' => 'Pajak',
                        'asuransi' => 'Asuransi',
                        'lain_lain' => 'Lain-lain',
                        default => $state,
                    })
                    ->colors([
                        'primary' => 'operasional',
                        'success' => 'gaji_karyawan',
                        'warning' => 'alat_medis',
                        'info' => 'obat_obatan',
                        'secondary' => 'listrik_air',
                        'danger' => 'sewa_tempat',
                        'gray' => 'lain_lain',
                    ])
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\BadgeColumn::make('status_validasi')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'Pending',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'disetujui',
                        'heroicon-o-x-circle' => 'ditolak',
                    ])
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\ImageColumn::make('bukti_transaksi')
                    ->label('Bukti')
                    ->circular()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('inputBy.name')
                    ->label('Diinput Oleh')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('validasiBy.name')
                    ->label('Divalidasi Oleh')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('validasi_at')
                    ->label('Tanggal Validasi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kategori')
                    ->label('Kategori')
                    ->options([
                        'operasional' => 'Operasional',
                        'gaji_karyawan' => 'Gaji Karyawan',
                        'alat_medis' => 'Alat Medis',
                        'obat_obatan' => 'Obat-obatan',
                        'listrik_air' => 'Listrik & Air',
                        'sewa_tempat' => 'Sewa Tempat',
                        'maintenance' => 'Maintenance',
                        'marketing' => 'Marketing',
                        'training' => 'Training',
                        'administrasi' => 'Administrasi',
                        'transportasi' => 'Transportasi',
                        'konsumsi' => 'Konsumsi',
                        'pajak' => 'Pajak',
                        'asuransi' => 'Asuransi',
                        'lain_lain' => 'Lain-lain',
                    ])
                    ->multiple()
                    ->searchable(),
                
                SelectFilter::make('status_validasi')
                    ->label('Status Validasi')
                    ->options([
                        'pending' => 'Pending',
                        'disetujui' => 'Disetujui',
                        'ditolak' => 'Ditolak',
                    ])
                    ->multiple(),
                
                Filter::make('tanggal')
                    ->label('Rentang Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('tanggal_dari')
                            ->label('Dari Tanggal')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                        Forms\Components\DatePicker::make('tanggal_sampai')
                            ->label('Sampai Tanggal')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['tanggal_dari'], fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '>=', $date))
                            ->when($data['tanggal_sampai'], fn (Builder $query, $date): Builder => $query->whereDate('tanggal', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        
                        if ($data['tanggal_dari'] ?? null) {
                            $indicators[] = 'Dari: ' . \Carbon\Carbon::parse($data['tanggal_dari'])->format('d/m/Y');
                        }
                        
                        if ($data['tanggal_sampai'] ?? null) {
                            $indicators[] = 'Sampai: ' . \Carbon\Carbon::parse($data['tanggal_sampai'])->format('d/m/Y');
                        }
                        
                        return $indicators;
                    }),
                
                Filter::make('nominal')
                    ->label('Rentang Nominal')
                    ->form([
                        Forms\Components\TextInput::make('nominal_min')
                            ->label('Nominal Minimum')
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\TextInput::make('nominal_max')
                            ->label('Nominal Maximum')
                            ->numeric()
                            ->prefix('Rp'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['nominal_min'], fn (Builder $query, $amount): Builder => $query->where('nominal', '>=', $amount))
                            ->when($data['nominal_max'], fn (Builder $query, $amount): Builder => $query->where('nominal', '<=', $amount));
                    }),
                
                Filter::make('has_receipt')
                    ->label('Dengan Bukti')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('bukti_transaksi')),
                
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color('info'),
                Tables\Actions\EditAction::make()
                    ->color('warning'),
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status_validasi' => 'disetujui',
                            'validasi_by' => Auth::id(),
                            'validasi_at' => now(),
                        ]);
                        
                        Notification::make()
                            ->title('Pengeluaran Disetujui')
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->status_validasi === 'pending'),
                Tables\Actions\DeleteAction::make()
                    ->color('danger'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Setujui Terpilih')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->status_validasi === 'pending') {
                                    $record->update([
                                        'status_validasi' => 'disetujui',
                                        'validasi_by' => Auth::id(),
                                        'validasi_at' => now(),
                                    ]);
                                }
                            }
                            
                            Notification::make()
                                ->title('Pengeluaran Terpilih Disetujui')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('tanggal', 'desc')
            ->poll('30s')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->deferLoading();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPengeluaran::route('/'),
            'create' => CreatePengeluaran::route('/create'),
            'view' => ViewPengeluaran::route('/{record}'),
            'edit' => EditPengeluaran::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
