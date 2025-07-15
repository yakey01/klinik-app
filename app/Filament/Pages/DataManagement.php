<?php

namespace App\Filament\Pages;

use App\Models\DataImport;
use App\Models\DataExport;
use App\Models\DataTransformation;
use App\Services\DataImportService;
use App\Services\DataExportService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class DataManagement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    
    protected static string $view = 'filament.pages.data-management';
    
    protected static ?string $navigationLabel = 'Data Management';
    
    protected static ?string $title = 'Data Management';
    
    protected static ?string $navigationGroup = 'System Administration';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $slug = 'data-management';
    
    // Page properties
    public $recentImports = [];
    public $recentExports = [];
    public $recentTransformations = [];
    public $importStats = [];
    public $exportStats = [];
    public $lastUpdate;
    
    public static function canAccess(): bool
    {
        return Auth::user()->hasRole(['super-admin', 'admin']);
    }
    
    public function mount(): void
    {
        $this->loadDataManagementData();
    }
    
    public function loadDataManagementData(): void
    {
        $importService = app(DataImportService::class);
        $exportService = app(DataExportService::class);
        
        $this->recentImports = DataImport::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        $this->recentExports = DataExport::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        $this->recentTransformations = DataTransformation::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        $this->importStats = $importService->getImportStats(Auth::user());
        $this->exportStats = $exportService->getExportStats(Auth::user());
        $this->lastUpdate = now()->format('Y-m-d H:i:s');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action('loadDataManagementData'),
                
            Action::make('create_import')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->modalHeading('Create Data Import')
                ->modalWidth('5xl')
                ->form([
                    Section::make('Basic Information')->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Select::make('source_type')
                                ->options(DataImport::getSourceTypes())
                                ->required()
                                ->reactive(),
                        ]),
                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                        Grid::make(2)->schema([
                            Select::make('target_model')
                                ->options(DataImport::getTargetModels())
                                ->required()
                                ->reactive(),
                        ]),
                    ]),
                    
                    Section::make('Source Configuration')->schema([
                        FileUpload::make('file')
                            ->label('Upload File')
                            ->acceptedFileTypes(['text/csv', 'application/vnd.ms-excel', 'application/json'])
                            ->visible(fn (callable $get) => in_array($get('source_type'), [
                                DataImport::SOURCE_FILE,
                                DataImport::SOURCE_CSV,
                                DataImport::SOURCE_EXCEL,
                                DataImport::SOURCE_JSON
                            ])),
                        KeyValue::make('source_config')
                            ->label('API Configuration')
                            ->keyLabel('Parameter')
                            ->valueLabel('Value')
                            ->visible(fn (callable $get) => $get('source_type') === DataImport::SOURCE_API),
                    ]),
                    
                    Section::make('Field Mapping')->schema([
                        Repeater::make('mapping_config')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('source_field')
                                        ->label('Source Field')
                                        ->required(),
                                    TextInput::make('target_field')
                                        ->label('Target Field')
                                        ->required(),
                                ])
                            ])
                            ->columns(2)
                            ->defaultItems(1),
                    ]),
                    
                    Section::make('Validation Rules')->schema([
                        KeyValue::make('validation_rules')
                            ->keyLabel('Field')
                            ->valueLabel('Rules (e.g., required|email)'),
                    ]),
                    
                    Section::make('Schedule Settings')->schema([
                        Grid::make(3)->schema([
                            Toggle::make('is_scheduled')
                                ->label('Schedule Import')
                                ->reactive(),
                            Select::make('schedule_frequency')
                                ->options(DataImport::getScheduleFrequencies())
                                ->visible(fn (callable $get) => $get('is_scheduled')),
                            Toggle::make('backup_before_import')
                                ->label('Create Backup')
                                ->default(true),
                        ]),
                    ]),
                ])
                ->action(function (array $data) {
                    $this->createImport($data);
                }),
                
            Action::make('create_export')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->modalHeading('Create Data Export')
                ->modalWidth('5xl')
                ->form([
                    Section::make('Basic Information')->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Select::make('source_model')
                                ->options(DataExport::getSourceModels())
                                ->required()
                                ->reactive(),
                        ]),
                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                        Grid::make(2)->schema([
                            Select::make('export_format')
                                ->options(DataExport::getExportFormats())
                                ->required(),
                        ]),
                    ]),
                    
                    Section::make('Query Configuration')->schema([
                        Repeater::make('filters')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('field')
                                        ->required(),
                                    Select::make('operator')
                                        ->options([
                                            '=' => 'Equals',
                                            '!=' => 'Not Equals',
                                            '>' => 'Greater Than',
                                            '<' => 'Less Than',
                                            'like' => 'Like',
                                            'in' => 'In',
                                        ])
                                        ->required(),
                                    TextInput::make('value')
                                        ->required(),
                                ])
                            ])
                            ->label('Filters')
                            ->collapsible()
                            ->defaultItems(0),
                    ]),
                    
                    Section::make('Column Configuration')->schema([
                        KeyValue::make('column_config')
                            ->keyLabel('Database Column')
                            ->valueLabel('Export Label')
                            ->required(),
                    ]),
                    
                    Section::make('Export Settings')->schema([
                        Grid::make(3)->schema([
                            Toggle::make('compress_output')
                                ->label('Compress File')
                                ->reactive(),
                            Select::make('compression_format')
                                ->options(DataExport::getCompressionFormats())
                                ->visible(fn (callable $get) => $get('compress_output')),
                            DateTimePicker::make('expires_at')
                                ->label('Expires At'),
                        ]),
                    ]),
                    
                    Section::make('Schedule Settings')->schema([
                        Grid::make(2)->schema([
                            Toggle::make('is_scheduled')
                                ->label('Schedule Export')
                                ->reactive(),
                            Select::make('schedule_frequency')
                                ->options(DataExport::getScheduleFrequencies())
                                ->visible(fn (callable $get) => $get('is_scheduled')),
                        ]),
                    ]),
                ])
                ->action(function (array $data) {
                    $this->createExport($data);
                }),
                
            Action::make('create_transformation')
                ->icon('heroicon-o-sparkles')
                ->color('warning')
                ->modalHeading('Create Data Transformation')
                ->modalWidth('5xl')
                ->form([
                    Section::make('Basic Information')->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Select::make('transformation_type')
                                ->options(DataTransformation::getTransformationTypes())
                                ->required()
                                ->reactive(),
                        ]),
                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                        Grid::make(2)->schema([
                            Select::make('source_model')
                                ->options(DataTransformation::getSourceModels())
                                ->required(),
                            Select::make('target_model')
                                ->options(DataTransformation::getSourceModels())
                                ->helperText('Leave empty to transform in place'),
                        ]),
                    ]),
                    
                    Section::make('Transformation Rules')->schema([
                        KeyValue::make('transformation_rules')
                            ->keyLabel('Rule')
                            ->valueLabel('Configuration')
                            ->required(),
                        KeyValue::make('field_mappings')
                            ->keyLabel('Source Field')
                            ->valueLabel('Target Field'),
                    ]),
                    
                    Section::make('Data Quality')->schema([
                        KeyValue::make('validation_rules')
                            ->keyLabel('Field')
                            ->valueLabel('Validation Rules'),
                        KeyValue::make('cleanup_rules')
                            ->keyLabel('Rule Type')
                            ->valueLabel('Configuration'),
                    ]),
                    
                    Section::make('Execution Settings')->schema([
                        Grid::make(3)->schema([
                            Toggle::make('dry_run')
                                ->label('Dry Run (Test Mode)')
                                ->default(true),
                            Toggle::make('backup_before_transform')
                                ->label('Create Backup')
                                ->default(true),
                            Toggle::make('is_scheduled')
                                ->label('Schedule Transformation')
                                ->reactive(),
                        ]),
                        Select::make('schedule_frequency')
                            ->options(DataTransformation::getScheduleFrequencies())
                            ->visible(fn (callable $get) => $get('is_scheduled')),
                    ]),
                ])
                ->action(function (array $data) {
                    $this->createTransformation($data);
                }),
        ];
    }
    
    public function createImport(array $data): void
    {
        $importService = app(DataImportService::class);
        
        try {
            // Convert mapping config from repeater format
            $mappingConfig = [];
            if (isset($data['mapping_config'])) {
                foreach ($data['mapping_config'] as $mapping) {
                    $mappingConfig[$mapping['target_field']] = $mapping['source_field'];
                }
            }
            $data['mapping_config'] = $mappingConfig;
            
            $import = $importService->createImport(Auth::user(), $data);
            
            // Handle file upload if present
            if (isset($data['file']) && $data['file']) {
                $importService->handleFileUpload($import, $data['file']);
            }
            
            $this->loadDataManagementData();
            
            Notification::make()
                ->title('Import created successfully')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to create import')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function createExport(array $data): void
    {
        $exportService = app(DataExportService::class);
        
        try {
            // Structure query config
            $queryConfig = [];
            if (isset($data['filters'])) {
                $queryConfig['filters'] = $data['filters'];
            }
            $data['query_config'] = $queryConfig;
            
            $export = $exportService->createExport(Auth::user(), $data);
            
            $this->loadDataManagementData();
            
            Notification::make()
                ->title('Export created successfully')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to create export')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function createTransformation(array $data): void
    {
        try {
            $transformation = DataTransformation::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'user_id' => Auth::id(),
                'transformation_type' => $data['transformation_type'],
                'source_model' => $data['source_model'],
                'target_model' => $data['target_model'] ?? null,
                'transformation_rules' => $data['transformation_rules'] ?? [],
                'field_mappings' => $data['field_mappings'] ?? [],
                'validation_rules' => $data['validation_rules'] ?? [],
                'cleanup_rules' => $data['cleanup_rules'] ?? [],
                'dry_run' => $data['dry_run'] ?? true,
                'backup_before_transform' => $data['backup_before_transform'] ?? true,
                'is_scheduled' => $data['is_scheduled'] ?? false,
                'schedule_frequency' => $data['schedule_frequency'] ?? null,
            ]);
            
            if ($transformation->is_scheduled && $transformation->schedule_frequency) {
                $transformation->scheduleNextRun();
            }
            
            $this->loadDataManagementData();
            
            Notification::make()
                ->title('Transformation created successfully')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to create transformation')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function executeImport(int $importId): void
    {
        $import = DataImport::findOrFail($importId);
        $importService = app(DataImportService::class);
        
        try {
            $importService->executeImport($import);
            
            Notification::make()
                ->title('Import executed successfully')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Import execution failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
        
        $this->loadDataManagementData();
    }
    
    public function executeExport(int $exportId): void
    {
        $export = DataExport::findOrFail($exportId);
        $exportService = app(DataExportService::class);
        
        try {
            $exportService->executeExport($export);
            
            Notification::make()
                ->title('Export executed successfully')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Export execution failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
        
        $this->loadDataManagementData();
    }
    
    public function downloadExport(int $exportId)
    {
        $export = DataExport::findOrFail($exportId);
        $exportService = app(DataExportService::class);
        
        try {
            return $exportService->downloadExport($export);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Download failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function previewImport(int $importId): void
    {
        $import = DataImport::findOrFail($importId);
        $importService = app(DataImportService::class);
        
        try {
            $preview = $importService->previewImport($import);
            
            Notification::make()
                ->title('Preview generated successfully')
                ->body("Found {$preview['total_rows']} rows with " . count($preview['columns']) . " columns")
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Preview failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
        
        $this->loadDataManagementData();
    }
    
    public function cancelOperation(string $type, int $id): void
    {
        try {
            match($type) {
                'import' => app(DataImportService::class)->cancelImport(DataImport::findOrFail($id)),
                'export' => app(DataExportService::class)->cancelExport(DataExport::findOrFail($id)),
                'transformation' => DataTransformation::findOrFail($id)->cancel(),
            };
            
            Notification::make()
                ->title('Operation cancelled successfully')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to cancel operation')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
        
        $this->loadDataManagementData();
    }
}