<?php

namespace App\Filament\Pages;

use App\Models\BulkOperation;
use App\Services\BulkOperationService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class BulkOperations extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    
    protected static string $view = 'filament.pages.bulk-operations';
    
    protected static ?string $navigationLabel = 'Create Bulk Operations';
    
    protected static ?string $title = 'Create Bulk Operations';
    
    protected static ?string $navigationGroup = 'System Administration';
    
    protected static ?int $navigationSort = 8;
    
    protected static ?string $slug = 'bulk-operations/create';
    
    // Page properties
    public $activeOperations = [];
    public $recentOperations = [];
    public $operationStats = [];
    public $availableModels = [];
    public $lastUpdate;
    
    public static function canAccess(): bool
    {
        return Auth::user()->hasRole(['super-admin', 'admin']);
    }
    
    public function mount(): void
    {
        $this->loadOperationsData();
    }
    
    public function loadOperationsData(): void
    {
        $bulkService = app(BulkOperationService::class);
        
        $this->activeOperations = BulkOperation::active()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        $this->recentOperations = BulkOperation::recent(24)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
            
        $this->operationStats = $bulkService->getOperationStats();
        $this->availableModels = $bulkService->getAvailableModels();
        $this->lastUpdate = now()->format('Y-m-d H:i:s');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action('loadOperationsData'),
                
            Action::make('new_bulk_update')
                ->icon('heroicon-o-pencil')
                ->color('primary')
                ->modalHeading('Create Bulk Update Operation')
                ->modalWidth('3xl')
                ->form([
                    Section::make('Operation Details')->schema([
                        Select::make('model_type')
                            ->label('Model Type')
                            ->options(collect($this->availableModels)->mapWithKeys(function ($model, $class) {
                                return [$class => $model['name']];
                            })->toArray())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('update_fields', [])),
                            
                        Repeater::make('filters')
                            ->label('Filters')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('field')
                                        ->label('Field')
                                        ->required(),
                                    Select::make('operator')
                                        ->label('Operator')
                                        ->options([
                                            '=' => 'Equals',
                                            '!=' => 'Not Equals',
                                            '>' => 'Greater Than',
                                            '>=' => 'Greater Than or Equal',
                                            '<' => 'Less Than',
                                            '<=' => 'Less Than or Equal',
                                            'like' => 'Like',
                                            'in' => 'In',
                                            'not_in' => 'Not In',
                                            'null' => 'Is Null',
                                            'not_null' => 'Is Not Null',
                                        ])
                                        ->default('=')
                                        ->required(),
                                    TextInput::make('value')
                                        ->label('Value')
                                        ->required(),
                                ])
                            ])
                            ->collapsible()
                            ->defaultItems(0),
                    ]),
                    
                    Section::make('Update Data')->schema([
                        Repeater::make('update_fields')
                            ->label('Fields to Update')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextInput::make('field')
                                        ->label('Field Name')
                                        ->required(),
                                    TextInput::make('value')
                                        ->label('New Value')
                                        ->required(),
                                ])
                            ])
                            ->minItems(1)
                            ->required(),
                    ]),
                ])
                ->action(function (array $data) {
                    $this->createBulkUpdateOperation($data);
                }),
                
            Action::make('new_bulk_delete')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->modalHeading('Create Bulk Delete Operation')
                ->modalWidth('3xl')
                ->requiresConfirmation()
                ->form([
                    Section::make('Operation Details')->schema([
                        Select::make('model_type')
                            ->label('Model Type')
                            ->options(collect($this->availableModels)->mapWithKeys(function ($model, $class) {
                                return [$class => $model['name']];
                            })->toArray())
                            ->required(),
                            
                        Repeater::make('filters')
                            ->label('Filters (Required for Delete)')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('field')
                                        ->label('Field')
                                        ->required(),
                                    Select::make('operator')
                                        ->label('Operator')
                                        ->options([
                                            '=' => 'Equals',
                                            '!=' => 'Not Equals',
                                            '>' => 'Greater Than',
                                            '>=' => 'Greater Than or Equal',
                                            '<' => 'Less Than',
                                            '<=' => 'Less Than or Equal',
                                            'like' => 'Like',
                                            'in' => 'In',
                                            'not_in' => 'Not In',
                                            'null' => 'Is Null',
                                            'not_null' => 'Is Not Null',
                                        ])
                                        ->default('=')
                                        ->required(),
                                    TextInput::make('value')
                                        ->label('Value')
                                        ->required(),
                                ])
                            ])
                            ->minItems(1)
                            ->required(),
                    ]),
                ])
                ->action(function (array $data) {
                    $this->createBulkDeleteOperation($data);
                }),
                
            Action::make('new_bulk_export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->modalHeading('Create Bulk Export Operation')
                ->modalWidth('3xl')
                ->form([
                    Section::make('Export Configuration')->schema([
                        Select::make('model_type')
                            ->label('Model Type')
                            ->options(collect($this->availableModels)->mapWithKeys(function ($model, $class) {
                                return [$class => $model['name']];
                            })->toArray())
                            ->required(),
                            
                        Select::make('format')
                            ->label('Export Format')
                            ->options([
                                'csv' => 'CSV',
                                'json' => 'JSON',
                            ])
                            ->default('csv')
                            ->required(),
                            
                        Textarea::make('columns')
                            ->label('Columns to Export (comma-separated, leave empty for all)')
                            ->placeholder('id,name,email,created_at')
                            ->helperText('Leave empty to export all columns'),
                            
                        Repeater::make('filters')
                            ->label('Filters (Optional)')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('field')
                                        ->label('Field')
                                        ->required(),
                                    Select::make('operator')
                                        ->label('Operator')
                                        ->options([
                                            '=' => 'Equals',
                                            '!=' => 'Not Equals',
                                            '>' => 'Greater Than',
                                            '>=' => 'Greater Than or Equal',
                                            '<' => 'Less Than',
                                            '<=' => 'Less Than or Equal',
                                            'like' => 'Like',
                                            'in' => 'In',
                                            'not_in' => 'Not In',
                                            'null' => 'Is Null',
                                            'not_null' => 'Is Not Null',
                                        ])
                                        ->default('=')
                                        ->required(),
                                    TextInput::make('value')
                                        ->label('Value')
                                        ->required(),
                                ])
                            ])
                            ->collapsible()
                            ->defaultItems(0),
                    ]),
                ])
                ->action(function (array $data) {
                    $this->createBulkExportOperation($data);
                }),
        ];
    }
    
    public function createBulkUpdateOperation(array $data): void
    {
        $bulkService = app(BulkOperationService::class);
        
        // Prepare update data
        $updateData = [];
        foreach ($data['update_fields'] as $field) {
            $updateData[$field['field']] = $field['value'];
        }
        
        $operation = $bulkService->createOperation(
            Auth::user(),
            BulkOperation::TYPE_UPDATE,
            $data['model_type'],
            ['update_data' => $updateData],
            $data['filters'] ?? []
        );
        
        // Queue the operation (in a real app, you'd use a job queue)
        dispatch(function () use ($bulkService, $operation) {
            $bulkService->executeBulkUpdate($operation);
        });
        
        $this->loadOperationsData();
        
        Notification::make()
            ->title('Bulk update operation created')
            ->success()
            ->send();
    }
    
    public function createBulkDeleteOperation(array $data): void
    {
        $bulkService = app(BulkOperationService::class);
        
        $operation = $bulkService->createOperation(
            Auth::user(),
            BulkOperation::TYPE_DELETE,
            $data['model_type'],
            [],
            $data['filters']
        );
        
        // Queue the operation
        dispatch(function () use ($bulkService, $operation) {
            $bulkService->executeBulkDelete($operation);
        });
        
        $this->loadOperationsData();
        
        Notification::make()
            ->title('Bulk delete operation created')
            ->success()
            ->send();
    }
    
    public function createBulkExportOperation(array $data): void
    {
        $bulkService = app(BulkOperationService::class);
        
        $columns = empty($data['columns']) ? ['*'] : array_map('trim', explode(',', $data['columns']));
        
        $operation = $bulkService->createOperation(
            Auth::user(),
            BulkOperation::TYPE_EXPORT,
            $data['model_type'],
            [
                'format' => $data['format'],
                'columns' => $columns,
            ],
            $data['filters'] ?? []
        );
        
        // Queue the operation
        dispatch(function () use ($bulkService, $operation) {
            $bulkService->executeBulkExport($operation);
        });
        
        $this->loadOperationsData();
        
        Notification::make()
            ->title('Bulk export operation created')
            ->success()
            ->send();
    }
    
    public function cancelOperation(int $operationId): void
    {
        $operation = BulkOperation::findOrFail($operationId);
        $bulkService = app(BulkOperationService::class);
        
        try {
            $bulkService->cancelOperation($operation);
            $this->loadOperationsData();
            
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
    }
    
    public function downloadExport(int $operationId)
    {
        $operation = BulkOperation::findOrFail($operationId);
        
        if ($operation->operation_type !== BulkOperation::TYPE_EXPORT || !$operation->isCompleted()) {
            Notification::make()
                ->title('Export not available')
                ->danger()
                ->send();
            return;
        }
        
        $filePath = $operation->operation_data['export_file'] ?? null;
        
        if (!$filePath || !Storage::exists($filePath)) {
            Notification::make()
                ->title('Export file not found')
                ->danger()
                ->send();
            return;
        }
        
        return Storage::download($filePath);
    }
    
    public function getOperationProgress(BulkOperation $operation): array
    {
        return [
            'percentage' => $operation->progress_percentage,
            'processed' => $operation->processed_records,
            'total' => $operation->total_records,
            'successful' => $operation->successful_records,
            'failed' => $operation->failed_records,
            'status' => $operation->status,
            'estimated_time' => $operation->getEstimatedTimeRemaining(),
        ];
    }
}