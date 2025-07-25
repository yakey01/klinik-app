<?php

namespace App\Filament\Traits;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkActionGroup;
use App\Services\BulkOperationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;

trait HasBulkOperations
{
    public static function getBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                static::getBulkDeleteAction(),
                static::getBulkExportAction(),
                static::getBulkUpdateAction(),
            ])->label('💾 Bulk Operations'),
        ];
    }

    public static function getHeaderActions(): array
    {
        return array_merge(parent::getHeaderActions() ?? [], [
            static::getBulkImportAction(),
            static::getBulkCreateAction(),
        ]);
    }

    protected static function getBulkDeleteAction(): BulkAction
    {
        return BulkAction::make('bulk_delete')
            ->label('🗑️ Delete Selected')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->modalHeading('Delete Selected Records')
            ->modalDescription('Are you sure you want to delete the selected records? This action cannot be undone.')
            ->modalSubmitActionLabel('Yes, Delete')
            ->action(function (Collection $records, array $data) {
                $bulkService = app(BulkOperationService::class);
                $modelClass = get_class($records->first());
                
                try {
                    $result = $bulkService->bulkDelete(
                        $modelClass,
                        $records->pluck('id')->toArray(),
                        ['soft_delete' => true]
                    );
                    
                    Notification::make()
                        ->title('✅ Bulk Delete Completed')
                        ->body("Successfully deleted {$result['deleted']} records.")
                        ->success()
                        ->send();
                        
                    if ($result['errors'] > 0) {
                        Notification::make()
                            ->title('⚠️ Some Errors Occurred')
                            ->body("{$result['errors']} records could not be deleted.")
                            ->warning()
                            ->send();
                    }
                    
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('❌ Bulk Delete Failed')
                        ->body('An error occurred while deleting records: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function getBulkExportAction(): BulkAction
    {
        return BulkAction::make('bulk_export')
            ->label('📤 Export Selected')
            ->color('info')
            ->icon('heroicon-o-arrow-down-tray')
            ->form([
                Toggle::make('include_relationships')
                    ->label('Include Related Data')
                    ->default(false)
                    ->helperText('Include related model data in export'),
            ])
            ->action(function (Collection $records, array $data) {
                $modelName = class_basename($records->first());
                $timestamp = now()->format('Y-m-d_H-i-s');
                $filename = "bulk_export_{$modelName}_{$timestamp}.json";
                
                $exportData = $records->map(function ($record) use ($data) {
                    $array = $record->toArray();
                    
                    if ($data['include_relationships'] && method_exists($record, 'getExportableRelations')) {
                        foreach ($record->getExportableRelations() as $relation) {
                            if ($record->relationLoaded($relation)) {
                                $array[$relation] = $record->$relation->toArray();
                            }
                        }
                    }
                    
                    return $array;
                })->toArray();
                
                Storage::put("exports/{$filename}", json_encode($exportData, JSON_PRETTY_PRINT));
                
                Notification::make()
                    ->title('📁 Export Completed')
                    ->body("Exported {$records->count()} records to {$filename}")
                    ->success()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('download')
                            ->label('📥 Download')
                            ->url(Storage::url("exports/{$filename}"))
                            ->openUrlInNewTab(),
                    ])
                    ->send();
            });
    }

    protected static function getBulkUpdateAction(): BulkAction
    {
        return BulkAction::make('bulk_update')
            ->label('✏️ Update Selected')
            ->color('warning')
            ->icon('heroicon-o-pencil')
            ->form(function (Collection $records) {
                $model = $records->first();
                $form = [];
                
                // Get fillable fields from the model
                foreach ($model->getFillable() as $field) {
                    if (in_array($field, ['created_at', 'updated_at', 'id'])) {
                        continue;
                    }
                    
                    $form[] = \Filament\Forms\Components\TextInput::make($field)
                        ->label(ucwords(str_replace('_', ' ', $field)))
                        ->placeholder("Leave empty to keep current value");
                }
                
                return $form;
            })
            ->action(function (Collection $records, array $data) {
                $bulkService = app(BulkOperationService::class);
                $modelClass = get_class($records->first());
                
                // Filter out empty values
                $updateData = array_filter($data, function ($value) {
                    return $value !== null && $value !== '';
                });
                
                if (empty($updateData)) {
                    Notification::make()
                        ->title('⚠️ No Updates')
                        ->body('No fields were provided for update.')
                        ->warning()
                        ->send();
                    return;
                }
                
                // Prepare update data with IDs
                $updates = $records->map(function ($record) use ($updateData) {
                    return array_merge(['id' => $record->id], $updateData);
                })->toArray();
                
                try {
                    $result = $bulkService->bulkUpdate($modelClass, $updates, 'id');
                    
                    Notification::make()
                        ->title('✅ Bulk Update Completed')
                        ->body("Successfully updated {$result['updated']} records.")
                        ->success()
                        ->send();
                        
                    if ($result['errors'] > 0) {
                        Notification::make()
                            ->title('⚠️ Some Errors Occurred')
                            ->body("{$result['errors']} records could not be updated.")
                            ->warning()
                            ->send();
                    }
                    
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('❌ Bulk Update Failed')
                        ->body('An error occurred while updating records: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function getBulkImportAction(): Action
    {
        return Action::make('bulk_import')
            ->label('📥 Bulk Import')
            ->color('success')
            ->icon('heroicon-o-arrow-up-tray')
            ->form([
                FileUpload::make('import_file')
                    ->label('Import File')
                    ->acceptedFileTypes(['application/json', 'text/csv'])
                    ->required()
                    ->helperText('Upload a JSON or CSV file with data to import'),
                    
                Toggle::make('validate_data')
                    ->label('Validate Before Import')
                    ->default(true)
                    ->helperText('Validate data before saving to database'),
                    
                Toggle::make('skip_duplicates')
                    ->label('Skip Duplicates')
                    ->default(true)
                    ->helperText('Skip records that already exist'),
            ])
            ->action(function (array $data) {
                $bulkService = app(BulkOperationService::class);
                $modelClass = static::getModel();
                
                try {
                    $filePath = $data['import_file'];
                    $fileContent = Storage::get($filePath);
                    
                    // Parse file based on extension
                    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                    
                    if ($extension === 'json') {
                        $importData = json_decode($fileContent, true);
                    } elseif ($extension === 'csv') {
                        $lines = explode("\n", $fileContent);
                        $headers = str_getcsv(array_shift($lines));
                        $importData = array_map(function ($line) use ($headers) {
                            return array_combine($headers, str_getcsv($line));
                        }, array_filter($lines));
                    } else {
                        throw new \Exception('Unsupported file format');
                    }
                    
                    if (empty($importData)) {
                        throw new \Exception('No data found in file');
                    }
                    
                    $result = $bulkService->bulkCreate(
                        $modelClass,
                        $importData,
                        [
                            'validate' => $data['validate_data'],
                            'skip_duplicates' => $data['skip_duplicates'],
                        ]
                    );
                    
                    Notification::make()
                        ->title('✅ Bulk Import Completed')
                        ->body("Successfully imported {$result['created']} records.")
                        ->success()
                        ->send();
                        
                    if ($result['errors'] > 0) {
                        Notification::make()
                            ->title('⚠️ Some Errors Occurred')
                            ->body("{$result['errors']} records could not be imported.")
                            ->warning()
                            ->send();
                    }
                    
                    // Clean up uploaded file
                    Storage::delete($filePath);
                    
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('❌ Import Failed')
                        ->body('An error occurred during import: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function getBulkCreateAction(): Action
    {
        return Action::make('bulk_create')
            ->label('➕ Bulk Create')
            ->color('success')
            ->icon('heroicon-o-plus-circle')
            ->form([
                Textarea::make('bulk_data')
                    ->label('JSON Data')
                    ->placeholder('Enter JSON array of objects to create...')
                    ->rows(10)
                    ->required()
                    ->helperText('Paste JSON data for bulk creation'),
                    
                Toggle::make('validate_data')
                    ->label('Validate Before Save')
                    ->default(true),
            ])
            ->action(function (array $data) {
                $bulkService = app(BulkOperationService::class);
                $modelClass = static::getModel();
                
                try {
                    $bulkData = json_decode($data['bulk_data'], true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception('Invalid JSON format: ' . json_last_error_msg());
                    }
                    
                    if (!is_array($bulkData)) {
                        throw new \Exception('JSON must be an array of objects');
                    }
                    
                    $result = $bulkService->bulkCreate(
                        $modelClass,
                        $bulkData,
                        ['validate' => $data['validate_data']]
                    );
                    
                    Notification::make()
                        ->title('✅ Bulk Create Completed')
                        ->body("Successfully created {$result['created']} records.")
                        ->success()
                        ->send();
                        
                    if ($result['errors'] > 0) {
                        Notification::make()
                            ->title('⚠️ Some Errors Occurred')
                            ->body("{$result['errors']} records could not be created.")
                            ->warning()
                            ->send();
                    }
                    
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('❌ Bulk Create Failed')
                        ->body('An error occurred: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}