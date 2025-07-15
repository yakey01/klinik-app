<?php

namespace App\Filament\Pages;

use App\Models\Report;
use App\Services\ReportService;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ReportBuilder extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static string $view = 'filament.pages.report-builder';
    
    protected static ?string $navigationLabel = 'Report Builder';
    
    protected static ?string $title = 'Report Builder';
    
    protected static ?string $navigationGroup = 'Reports & Analytics';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $slug = 'report-builder';
    
    // Page properties
    public $myReports = [];
    public $publicReports = [];
    public $reportTemplates = [];
    public $availableModels = [];
    public $reportStats = [];
    public $lastUpdate;
    
    public static function canAccess(): bool
    {
        return Auth::user()->hasRole(['super-admin', 'admin', 'manajer']);
    }
    
    public function mount(): void
    {
        $this->loadReportsData();
    }
    
    public function loadReportsData(): void
    {
        $reportService = app(ReportService::class);
        
        $this->myReports = Report::where('user_id', Auth::id())
            ->active()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        $this->publicReports = Report::where('is_public', true)
            ->active()
            ->orderBy('last_generated_at', 'desc')
            ->limit(10)
            ->get();
            
        $this->reportTemplates = Report::getReportTemplates();
        $this->availableModels = Report::getAvailableModels();
        $this->reportStats = $reportService->getSystemStats();
        $this->lastUpdate = now()->format('Y-m-d H:i:s');
    }
    
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->action('loadReportsData'),
                
            Action::make('create_custom_report')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->modalHeading('Create Custom Report')
                ->modalWidth('4xl')
                ->form([
                    Section::make('Basic Information')->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Report Name')
                                ->required()
                                ->maxLength(255),
                            Select::make('category')
                                ->label('Category')
                                ->options([
                                    Report::CATEGORY_FINANCIAL => 'Financial',
                                    Report::CATEGORY_OPERATIONAL => 'Operational',
                                    Report::CATEGORY_MEDICAL => 'Medical',
                                    Report::CATEGORY_ADMINISTRATIVE => 'Administrative',
                                    Report::CATEGORY_SECURITY => 'Security',
                                    Report::CATEGORY_PERFORMANCE => 'Performance',
                                    Report::CATEGORY_CUSTOM => 'Custom',
                                ])
                                ->required(),
                        ]),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->columnSpanFull(),
                        Grid::make(2)->schema([
                            Select::make('report_type')
                                ->label('Report Type')
                                ->options([
                                    Report::TYPE_TABLE => 'Table',
                                    Report::TYPE_CHART => 'Chart',
                                    Report::TYPE_KPI => 'KPI Dashboard',
                                ])
                                ->required()
                                ->reactive(),
                            Select::make('model')
                                ->label('Data Source')
                                ->options(collect($this->availableModels)->mapWithKeys(function ($model, $class) {
                                    return [$class => $model['name']];
                                }))
                                ->required()
                                ->reactive(),
                        ]),
                    ]),
                    
                    Section::make('Query Configuration')->schema([
                        Select::make('columns')
                            ->label('Columns to Include')
                            ->multiple()
                            ->options(function (callable $get) {
                                $model = $get('model');
                                if ($model && isset($this->availableModels[$model])) {
                                    return array_combine(
                                        $this->availableModels[$model]['fields'],
                                        $this->availableModels[$model]['fields']
                                    );
                                }
                                return [];
                            })
                            ->visible(fn (callable $get) => $get('report_type') === Report::TYPE_TABLE),
                            
                        Repeater::make('aggregations')
                            ->label('Aggregations')
                            ->schema([
                                Grid::make(3)->schema([
                                    Select::make('function')
                                        ->label('Function')
                                        ->options([
                                            'count' => 'Count',
                                            'sum' => 'Sum',
                                            'avg' => 'Average',
                                            'min' => 'Minimum',
                                            'max' => 'Maximum',
                                        ])
                                        ->required(),
                                    Select::make('field')
                                        ->label('Field')
                                        ->options(function (callable $get) {
                                            $model = $get('../../../model');
                                            if ($model && isset($this->availableModels[$model])) {
                                                return array_combine(
                                                    $this->availableModels[$model]['aggregatable'],
                                                    $this->availableModels[$model]['aggregatable']
                                                );
                                            }
                                            return [];
                                        })
                                        ->required(),
                                    TextInput::make('alias')
                                        ->label('Alias')
                                        ->required(),
                                ])
                            ])
                            ->visible(fn (callable $get) => in_array($get('report_type'), [Report::TYPE_CHART, Report::TYPE_KPI]))
                            ->defaultItems(1),
                    ]),
                    
                    Section::make('Chart Configuration')->schema([
                        Select::make('chart_type')
                            ->label('Chart Type')
                            ->options([
                                Report::CHART_BAR => 'Bar Chart',
                                Report::CHART_LINE => 'Line Chart',
                                Report::CHART_PIE => 'Pie Chart',
                                Report::CHART_AREA => 'Area Chart',
                                Report::CHART_DOUGHNUT => 'Doughnut Chart',
                            ])
                            ->required(),
                        Grid::make(2)->schema([
                            TextInput::make('label_field')
                                ->label('Label Field')
                                ->helperText('Field to use for chart labels'),
                            TextInput::make('data_fields')
                                ->label('Data Fields')
                                ->helperText('Comma-separated field names for data'),
                        ]),
                    ])->visible(fn (callable $get) => $get('report_type') === Report::TYPE_CHART),
                    
                    Section::make('Filters')->schema([
                        Repeater::make('filters')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextInput::make('field')
                                        ->label('Field')
                                        ->required(),
                                    Select::make('operator')
                                        ->label('Operator')
                                        ->options([
                                            '=' => 'Equals',
                                            '!=' => 'Not Equals',
                                            '>' => 'Greater Than',
                                            '<' => 'Less Than',
                                            'like' => 'Like',
                                            'in' => 'In',
                                            'date_range' => 'Date Range',
                                        ])
                                        ->required(),
                                    TextInput::make('value')
                                        ->label('Default Value'),
                                    TextInput::make('parameter')
                                        ->label('Parameter Name')
                                        ->helperText('For runtime filtering'),
                                ])
                            ])
                            ->collapsible()
                            ->defaultItems(0),
                    ]),
                    
                    Section::make('Settings')->schema([
                        Grid::make(3)->schema([
                            Toggle::make('is_public')
                                ->label('Make Public')
                                ->helperText('Allow other users to view this report'),
                            TextInput::make('cache_duration')
                                ->label('Cache Duration (seconds)')
                                ->numeric()
                                ->default(300),
                            Select::make('tags')
                                ->label('Tags')
                                ->multiple()
                                ->options([
                                    'daily' => 'Daily',
                                    'weekly' => 'Weekly',
                                    'monthly' => 'Monthly',
                                    'important' => 'Important',
                                    'automated' => 'Automated',
                                ])
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->required(),
                                ]),
                        ]),
                    ]),
                ])
                ->action(function (array $data) {
                    $this->createCustomReport($data);
                }),
                
            Action::make('create_from_template')
                ->icon('heroicon-o-document-duplicate')
                ->color('info')
                ->modalHeading('Create Report from Template')
                ->modalWidth('2xl')
                ->form([
                    Select::make('template')
                        ->label('Select Template')
                        ->options(collect($this->reportTemplates)->pluck('name', 'name'))
                        ->required()
                        ->reactive(),
                    TextInput::make('name')
                        ->label('Report Name')
                        ->required(),
                    Textarea::make('description')
                        ->label('Description')
                        ->rows(2),
                    Toggle::make('is_public')
                        ->label('Make Public'),
                ])
                ->action(function (array $data) {
                    $this->createFromTemplate($data);
                }),
        ];
    }
    
    public function createCustomReport(array $data): void
    {
        $reportService = app(ReportService::class);
        
        // Build query configuration
        $queryConfig = [
            'model' => $data['model'],
        ];
        
        // Add columns for table reports
        if ($data['report_type'] === Report::TYPE_TABLE && isset($data['columns'])) {
            $queryConfig['columns'] = $data['columns'];
        }
        
        // Add aggregations for chart/KPI reports
        if (isset($data['aggregations']) && !empty($data['aggregations'])) {
            $queryConfig['aggregations'] = $data['aggregations'];
        }
        
        // Build chart configuration
        $chartConfig = null;
        if ($data['report_type'] === Report::TYPE_CHART) {
            $chartConfig = [
                'type' => $data['chart_type'],
                'label_field' => $data['label_field'] ?? 'id',
                'data_fields' => isset($data['data_fields']) ? explode(',', $data['data_fields']) : [],
            ];
        }
        
        $reportData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'report_type' => $data['report_type'],
            'category' => $data['category'],
            'query_config' => $queryConfig,
            'chart_config' => $chartConfig,
            'filters' => $data['filters'] ?? [],
            'is_public' => $data['is_public'] ?? false,
            'cache_duration' => $data['cache_duration'] ?? 300,
            'tags' => $data['tags'] ?? [],
        ];
        
        $report = $reportService->createReport(Auth::user(), $reportData);
        
        $this->loadReportsData();
        
        Notification::make()
            ->title('Report created successfully')
            ->success()
            ->send();
    }
    
    public function createFromTemplate(array $data): void
    {
        $reportService = app(ReportService::class);
        
        $templateKey = array_search($data['template'], collect($this->reportTemplates)->pluck('name')->toArray());
        
        if (!$templateKey) {
            Notification::make()
                ->title('Template not found')
                ->danger()
                ->send();
            return;
        }
        
        $customizations = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_public' => $data['is_public'] ?? false,
        ];
        
        $report = $reportService->createFromTemplate(Auth::user(), $templateKey, $customizations);
        
        $this->loadReportsData();
        
        Notification::make()
            ->title('Report created from template')
            ->success()
            ->send();
    }
    
    public function executeReport(int $reportId): void
    {
        $report = Report::findOrFail($reportId);
        $reportService = app(ReportService::class);
        
        try {
            $execution = $reportService->executeReport($report, Auth::user());
            
            Notification::make()
                ->title('Report executed successfully')
                ->body("Execution completed in {$execution->getFormattedExecutionTime()}")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Report execution failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function duplicateReport(int $reportId): void
    {
        $report = Report::findOrFail($reportId);
        
        $newReport = $report->replicate();
        $newReport->name = $report->name . ' (Copy)';
        $newReport->user_id = Auth::id();
        $newReport->is_public = false;
        $newReport->save();
        
        $this->loadReportsData();
        
        Notification::make()
            ->title('Report duplicated successfully')
            ->success()
            ->send();
    }
    
    public function getReportTypeIcon(string $type): string
    {
        return match($type) {
            Report::TYPE_TABLE => 'heroicon-o-table-cells',
            Report::TYPE_CHART => 'heroicon-o-chart-bar',
            Report::TYPE_DASHBOARD => 'heroicon-o-squares-2x2',
            Report::TYPE_KPI => 'heroicon-o-chart-pie',
            default => 'heroicon-o-document-text',
        };
    }
    
    public function getReportCategoryColor(string $category): string
    {
        return match($category) {
            Report::CATEGORY_FINANCIAL => 'success',
            Report::CATEGORY_OPERATIONAL => 'primary',
            Report::CATEGORY_MEDICAL => 'info',
            Report::CATEGORY_ADMINISTRATIVE => 'warning',
            Report::CATEGORY_SECURITY => 'danger',
            Report::CATEGORY_PERFORMANCE => 'secondary',
            default => 'gray',
        };
    }
}