<?php

namespace App\Filament\Petugas\Resources;

use App\Filament\Petugas\Resources\PasienResource\Pages;
use App\Filament\Petugas\Resources\TindakanResource;
use App\Models\Pasien;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Services\BulkOperationService;
use App\Services\ExportImportService;
use App\Services\AdvancedSearchService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Exception;

class PasienResource extends Resource
{
    protected static ?string $model = Pasien::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';
    
    protected static ?string $navigationGroup = '🏥 Manajemen Pasien';
    
    protected static ?string $navigationLabel = 'Input Pasien';
    
    protected static ?string $modelLabel = 'Pasien';
    
    protected static ?string $pluralModelLabel = 'Input Pasien';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('no_rekam_medis')
                            ->label('No. Rekam Medis')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20)
                            ->placeholder('Contoh: RM-2024-001')
                            ->helperText('Nomor rekam medis akan di-generate otomatis jika dikosongkan')
                            ->default(fn () => 'RM-' . date('Y') . '-' . str_pad(Pasien::count() + 1, 3, '0', STR_PAD_LEFT)),
                        
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nama lengkap pasien'),
                        
                        Forms\Components\DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->required()
                            ->maxDate(now())
                            ->placeholder('Pilih tanggal lahir'),
                        
                        Forms\Components\Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->required()
                            ->options([
                                'L' => 'Laki-laki',
                                'P' => 'Perempuan',
                            ])
                            ->placeholder('Pilih jenis kelamin'),
                        
                        Forms\Components\Textarea::make('alamat')
                            ->label('Alamat')
                            ->maxLength(500)
                            ->placeholder('Masukkan alamat lengkap')
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('no_telepon')
                            ->label('No. Telepon')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('Contoh: 08123456789'),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('Contoh: pasien@email.com'),
                        
                        Forms\Components\TextInput::make('pekerjaan')
                            ->label('Pekerjaan')
                            ->maxLength(100)
                            ->placeholder('Contoh: Karyawan swasta'),
                        
                        Forms\Components\Select::make('status_pernikahan')
                            ->label('Status Pernikahan')
                            ->options([
                                'belum_menikah' => 'Belum Menikah',
                                'menikah' => 'Menikah',
                                'janda' => 'Janda',
                                'duda' => 'Duda',
                            ])
                            ->placeholder('Pilih status pernikahan'),
                        
                        Forms\Components\TextInput::make('kontak_darurat_nama')
                            ->label('Nama Kontak Darurat')
                            ->maxLength(255)
                            ->placeholder('Nama keluarga/kerabat'),
                        
                        Forms\Components\TextInput::make('kontak_darurat_telepon')
                            ->label('No. Telepon Kontak Darurat')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('Contoh: 08123456789'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('no_rekam_medis')
                    ->label('No. RM')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Pasien')
                    ->searchable()
                    ->limit(30)
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('tanggal_lahir')
                    ->label('Tgl. Lahir')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(fn (Pasien $record): string => $record->umur ? $record->umur . ' tahun' : ''),
                
                Tables\Columns\TextColumn::make('jenis_kelamin')
                    ->label('JK')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'L' => 'info',
                        'P' => 'success',
                    }),
                
                Tables\Columns\TextColumn::make('no_telepon')
                    ->label('No. Telepon')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('alamat')
                    ->label('Alamat')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_kelamin')
                    ->label('Jenis Kelamin')
                    ->options([
                        'L' => 'Laki-laki',
                        'P' => 'Perempuan',
                    ]),
                
                Tables\Filters\Filter::make('tanggal_lahir')
                    ->form([
                        Forms\Components\DatePicker::make('dari')
                            ->label('Lahir Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('Lahir Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_lahir', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_lahir', '<=', $date),
                            );
                    }),
                
                Tables\Filters\SelectFilter::make('status_pernikahan')
                    ->label('Status Pernikahan')
                    ->options([
                        'belum_menikah' => 'Belum Menikah',
                        'menikah' => 'Menikah',
                        'janda' => 'Janda',
                        'duda' => 'Duda',
                    ]),
            ])
            ->headerActions([
                // Advanced Search Action
                Tables\Actions\Action::make('advanced_search')
                    ->label('🔍 Pencarian Lanjutan')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->modalHeading('🔍 Pencarian Lanjutan Pasien')
                    ->modalDescription('Gunakan filter lanjutan untuk mencari pasien dengan kriteria spesifik.')
                    ->modalSubmitActionLabel('Cari')
                    ->form([
                        Repeater::make('filters')
                            ->label('Filter Pencarian')
                            ->schema([
                                Select::make('field')
                                    ->label('Field')
                                    ->options([
                                        'nama' => 'Nama',
                                        'no_rekam_medis' => 'No. Rekam Medis',
                                        'no_telepon' => 'No. Telepon',
                                        'alamat' => 'Alamat',
                                        'jenis_kelamin' => 'Jenis Kelamin',
                                        'tanggal_lahir' => 'Tanggal Lahir',
                                        'status_pernikahan' => 'Status Pernikahan',
                                        'email' => 'Email',
                                        'pekerjaan' => 'Pekerjaan',
                                    ])
                                    ->required(),
                                Select::make('operator')
                                    ->label('Operator')
                                    ->options([
                                        'equals' => 'Sama dengan',
                                        'contains' => 'Mengandung',
                                        'starts_with' => 'Dimulai dengan',
                                        'ends_with' => 'Diakhiri dengan',
                                        'not_equals' => 'Tidak sama dengan',
                                        'greater_than' => 'Lebih besar dari',
                                        'less_than' => 'Lebih kecil dari',
                                        'date_equals' => 'Tanggal sama dengan',
                                        'date_before' => 'Tanggal sebelum',
                                        'date_after' => 'Tanggal setelah',
                                        'is_null' => 'Kosong',
                                        'is_not_null' => 'Tidak kosong',
                                    ])
                                    ->required(),
                                TextInput::make('value')
                                    ->label('Nilai')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Filter')
                            ->collapsed(),
                        TextInput::make('search_term')
                            ->label('Kata Kunci Pencarian')
                            ->placeholder('Cari di semua field...')
                            ->helperText('Pencarian akan dilakukan di semua field yang dapat dicari'),
                        TextInput::make('save_search_name')
                            ->label('Simpan Pencarian (Opsional)')
                            ->placeholder('Nama pencarian untuk disimpan')
                            ->helperText('Masukkan nama untuk menyimpan pencarian ini'),
                    ])
                    ->action(function (array $data) {
                        try {
                            $searchService = new AdvancedSearchService();
                            
                            $searchParams = [
                                'filters' => $data['filters'] ?? [],
                                'search' => $data['search_term'] ?? '',
                                'per_page' => 25,
                                'page' => 1
                            ];
                            
                            // Save search if name provided
                            if (!empty($data['save_search_name'])) {
                                $searchService->saveSearch(
                                    Pasien::class,
                                    $searchParams,
                                    $data['save_search_name']
                                );
                            }
                            
                            // Apply search (this would typically modify the table query)
                            $results = $searchService->search(Pasien::class, $searchParams);
                            
                            Notification::make()
                                ->title('🔍 Pencarian Berhasil')
                                ->body("Ditemukan {$results['pagination']['total']} pasien")
                                ->success()
                                ->send();
                                
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('❌ Pencarian Gagal')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                // Saved Searches Action
                Tables\Actions\Action::make('saved_searches')
                    ->label('💾 Pencarian Tersimpan')
                    ->icon('heroicon-o-bookmark')
                    ->color('success')
                    ->modalHeading('💾 Pencarian Tersimpan')
                    ->modalDescription('Akses pencarian yang telah disimpan sebelumnya.')
                    ->modalSubmitActionLabel('Gunakan')
                    ->form([
                        Select::make('saved_search_id')
                            ->label('Pilih Pencarian')
                            ->options(function () {
                                try {
                                    $searchService = new AdvancedSearchService();
                                    $savedSearches = $searchService->getSavedSearches(Pasien::class);
                                    
                                    return collect($savedSearches['data'])->pluck('name', 'id');
                                } catch (Exception $e) {
                                    return [];
                                }
                            })
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        try {
                            $searchService = new AdvancedSearchService();
                            $savedSearches = $searchService->getSavedSearches(Pasien::class);
                            
                            $selectedSearch = collect($savedSearches['data'])
                                ->firstWhere('id', $data['saved_search_id']);
                            
                            if ($selectedSearch) {
                                $results = $searchService->search(Pasien::class, $selectedSearch['search_params']);
                                
                                Notification::make()
                                    ->title('💾 Pencarian Berhasil')
                                    ->body("Menggunakan pencarian '{$selectedSearch['name']}' - Ditemukan {$results['pagination']['total']} pasien")
                                    ->success()
                                    ->send();
                            }
                            
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('❌ Pencarian Gagal')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                
                // Import Data Action
                Tables\Actions\Action::make('import_data')
                    ->label('📥 Import Data')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('warning')
                    ->modalHeading('📥 Import Data Pasien')
                    ->modalDescription('Upload file Excel, CSV, atau JSON untuk mengimpor data pasien.')
                    ->modalSubmitActionLabel('Import')
                    ->form([
                        FileUpload::make('import_file')
                            ->label('File Import')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/json'])
                            ->required()
                            ->helperText('Format yang didukung: Excel (.xlsx), CSV (.csv), JSON (.json)'),
                        Select::make('import_options')
                            ->label('Opsi Import')
                            ->options([
                                'skip_duplicates' => 'Lewati data duplikat',
                                'update_existing' => 'Update data yang sudah ada',
                                'validate_only' => 'Validasi saja (tidak import)',
                            ])
                            ->multiple()
                            ->helperText('Pilih opsi import yang diinginkan'),
                    ])
                    ->action(function (array $data) {
                        try {
                            $importService = new ExportImportService();
                            
                            if (!empty($data['import_file'])) {
                                $filePath = $data['import_file'];
                                $options = [
                                    'skip_duplicates' => in_array('skip_duplicates', $data['import_options'] ?? []),
                                    'validate' => !in_array('validate_only', $data['import_options'] ?? []),
                                ];
                                
                                if (in_array('validate_only', $data['import_options'] ?? [])) {
                                    $result = $importService->validateImportData(Pasien::class, $filePath);
                                    
                                    Notification::make()
                                        ->title('✅ Validasi Selesai')
                                        ->body("Valid: {$result['valid']}, Invalid: {$result['invalid']}, Total: {$result['total']}")
                                        ->success()
                                        ->send();
                                } else {
                                    $result = $importService->importData(Pasien::class, $filePath, $options);
                                    
                                    Notification::make()
                                        ->title('📥 Import Berhasil')
                                        ->body("Berhasil import {$result['imported']} pasien, Error: {$result['errors']}")
                                        ->success()
                                        ->send();
                                }
                            }
                            
                        } catch (Exception $e) {
                            Notification::make()
                                ->title('❌ Import Gagal')
                                ->body('Terjadi kesalahan: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('create_tindakan')
                        ->label('🏥 Buat Tindakan')
                        ->icon('heroicon-o-plus-circle')
                        ->color('primary')
                        ->url(fn (Pasien $record): string => TindakanResource::getUrl('create', [], panel: 'petugas') . '?pasien_id=' . $record->id)
                        ->tooltip('Buat tindakan untuk pasien ini'),
                    
                    Tables\Actions\ViewAction::make()
                        ->label('👁️ Lihat'),
                    Tables\Actions\EditAction::make()
                        ->label('✏️ Edit'),
                    Tables\Actions\DeleteAction::make()
                        ->label('🗑️ Hapus'),
                ])
                ->label('⚙️ Aksi')
                ->icon('heroicon-o-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => Auth::user()->can('delete_any_pasien')),
                    
                    // Export selected patients
                    Tables\Actions\BulkAction::make('export_selected')
                        ->label('📤 Export Terpilih')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Export Data Pasien')
                        ->modalDescription('Export data pasien yang dipilih ke format file.')
                        ->modalSubmitActionLabel('Export')
                        ->form([
                            Select::make('format')
                                ->label('Format File')
                                ->options([
                                    'xlsx' => 'Excel (.xlsx)',
                                    'csv' => 'CSV (.csv)',
                                    'json' => 'JSON (.json)',
                                ])
                                ->default('xlsx')
                                ->required(),
                            Toggle::make('include_relations')
                                ->label('Sertakan Data Terkait')
                                ->helperText('Sertakan data tindakan dan relasi lainnya')
                                ->default(false),
                        ])
                        ->action(function (Collection $records, array $data) {
                            try {
                                $exportService = new ExportImportService();
                                $ids = $records->pluck('id')->toArray();
                                
                                // Create temporary filtered export
                                $result = $exportService->exportData(
                                    Pasien::class,
                                    [
                                        'format' => $data['format'],
                                        'include_relations' => $data['include_relations'],
                                        'filters' => ['id' => $ids]
                                    ]
                                );
                                
                                // Trigger download
                                return response()->download(
                                    storage_path('app/' . $result['file_path']),
                                    $result['file_name']
                                );
                                
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('❌ Export Gagal')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    // Bulk update status
                    Tables\Actions\BulkAction::make('bulk_update_status')
                        ->label('🔄 Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Update Status Pasien')
                        ->modalDescription('Update status untuk pasien yang dipilih.')
                        ->modalSubmitActionLabel('Update')
                        ->form([
                            Select::make('status_pernikahan')
                                ->label('Status Pernikahan')
                                ->options([
                                    'belum_menikah' => 'Belum Menikah',
                                    'menikah' => 'Menikah',
                                    'janda' => 'Janda',
                                    'duda' => 'Duda',
                                ])
                                ->nullable(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            try {
                                $updateData = array_filter($data);
                                if (empty($updateData)) {
                                    Notification::make()
                                        ->title('⚠️ Tidak Ada Data')
                                        ->body('Pilih minimal satu field untuk diupdate.')
                                        ->warning()
                                        ->send();
                                    return;
                                }
                                
                                $bulkService = new BulkOperationService();
                                $updates = $records->map(function ($record) use ($updateData) {
                                    return array_merge(['id' => $record->id], $updateData);
                                })->toArray();
                                
                                $result = $bulkService->bulkUpdate(
                                    Pasien::class,
                                    $updates,
                                    'id',
                                    ['validate' => false]
                                );
                                
                                Notification::make()
                                    ->title('✅ Update Berhasil')
                                    ->body("Berhasil update {$result['updated']} pasien.")
                                    ->success()
                                    ->send();
                                    
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('❌ Update Gagal')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    
                    // Bulk assign to user
                    Tables\Actions\BulkAction::make('bulk_assign')
                        ->label('👤 Assign ke User')
                        ->icon('heroicon-o-user-plus')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Assign Pasien ke User')
                        ->modalDescription('Assign pasien yang dipilih ke user tertentu.')
                        ->modalSubmitActionLabel('Assign')
                        ->form([
                            Select::make('user_id')
                                ->label('User')
                                ->options(function () {
                                    return \App\Models\User::whereHas('roles', function ($query) {
                                        $query->where('name', 'petugas');
                                    })->pluck('name', 'id');
                                })
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            try {
                                $bulkService = new BulkOperationService();
                                $updates = $records->map(function ($record) use ($data) {
                                    return [
                                        'id' => $record->id,
                                        'input_by' => $data['user_id']
                                    ];
                                })->toArray();
                                
                                $result = $bulkService->bulkUpdate(
                                    Pasien::class,
                                    $updates,
                                    'id',
                                    ['validate' => false]
                                );
                                
                                Notification::make()
                                    ->title('✅ Assign Berhasil')
                                    ->body("Berhasil assign {$result['updated']} pasien.")
                                    ->success()
                                    ->send();
                                    
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('❌ Assign Gagal')
                                    ->body('Terjadi kesalahan: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('input_by', auth()->id())
            ->with(['inputBy'])
            ->orderBy('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPasiens::route('/'),
            'create' => Pages\CreatePasien::route('/create'),
            'view' => Pages\ViewPasien::route('/{record}'),
            'edit' => Pages\EditPasien::route('/{record}/edit'),
        ];
    }

    public static function getUrl(string $name = 'index', array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null): string
    {
        $panel = $panel ?? 'petugas';
        return parent::getUrl($name, $parameters, $isAbsolute, $panel, $tenant);
    }
}