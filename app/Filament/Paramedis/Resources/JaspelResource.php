<?php

namespace App\Filament\Paramedis\Resources;

use App\Filament\Paramedis\Resources\JaspelResource\Pages;
use App\Models\Jaspel;
use App\Models\JenisTindakan;
use App\Models\Tindakan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;

class JaspelResource extends Resource
{
    protected static ?string $model = Jaspel::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Jaspel Saya';
    protected static ?string $modelLabel = 'Jaspel';
    protected static ?string $pluralModelLabel = 'Data Jaspel';
    protected static ?string $navigationGroup = 'Jaspel';
    protected static ?int $navigationSort = 1;

    // Security: Only accessible by paramedis role
    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->role?->name === 'paramedis';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Jaspel')
                    ->schema([
                        Forms\Components\Select::make('tindakan_id')
                            ->label('Tindakan')
                            ->relationship('tindakan', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                $record->jenisTindakan->nama . ' - ' . $record->pasien->nama
                            )
                            ->disabled(),

                        Forms\Components\DatePicker::make('tanggal')
                            ->label('Tanggal')
                            ->required()
                            ->disabled(),

                        Forms\Components\TextInput::make('jumlah')
                            ->label('Jumlah Jaspel')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled(),

                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(3)
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tindakan.jenisTindakan.nama')
                    ->label('Jenis Tindakan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tindakan.pasien.nama')
                    ->label('Pasien')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('jumlah')
                    ->label('Jumlah Jaspel')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved', 
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => '⏳ Pending',
                        'approved' => '✅ Disetujui',
                        'rejected' => '❌ Ditolak',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(40)
                    ->placeholder('Tidak ada'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('this_month')
                    ->label('Bulan Ini')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereMonth('tanggal', Carbon::now()->month)
                              ->whereYear('tanggal', Carbon::now()->year)
                    )
                    ->default(),

                Filter::make('this_week')
                    ->label('Minggu Ini')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereBetween('tanggal', [
                            Carbon::now()->startOfWeek(),
                            Carbon::now()->endOfWeek()
                        ])
                    ),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),

                Filter::make('approved_only')
                    ->label('Hanya Disetujui')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'approved')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('tanggal', 'desc')
            ->poll('60s'); // Auto refresh every minute
    }

    public static function getEloquentQuery(): Builder
    {
        // Security: Only show current user's jaspel records
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJaspels::route('/'),
            'view' => Pages\ViewJaspel::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Jaspel created automatically from tindakan
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false; // Cannot edit jaspel
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false; // Cannot delete jaspel
    }
}
