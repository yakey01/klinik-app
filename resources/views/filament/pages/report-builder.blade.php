<div class="space-y-6">
    <!-- Report Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <x-heroicon-o-chart-bar class="h-6 w-6 text-blue-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Reports</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $reportStats['total_reports'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                    <x-heroicon-o-eye class="h-6 w-6 text-green-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Public Reports</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $reportStats['public_reports'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                    <x-heroicon-o-play class="h-6 w-6 text-purple-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Executions Today</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $reportStats['executions_today'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
                    <x-heroicon-o-clock class="h-6 w-6 text-yellow-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Avg Execution Time</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $reportStats['avg_execution_time'] ? number_format($reportStats['avg_execution_time']) . 'ms' : 'N/A' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Templates -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Report Templates</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Quick start with pre-built report templates
            </p>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($reportTemplates as $key => $template)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <x-dynamic-component 
                                    :component="$this->getReportTypeIcon($template['type'])" 
                                    class="h-6 w-6 text-gray-600 dark:text-gray-400" 
                                />
                                <span class="ml-2 text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $template['name'] }}
                                </span>
                            </div>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $this->getReportCategoryColor($template['category']) }}-100 text-{{ $this->getReportCategoryColor($template['category']) }}-800">
                                {{ ucfirst($template['category']) }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                            {{ $template['description'] }}
                        </p>
                        <button 
                            type="button" 
                            wire:click="createFromTemplate('{{ $key }}')"
                            class="w-full inline-flex items-center justify-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            <x-heroicon-o-plus class="h-4 w-4 mr-1" />
                            Use Template
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- My Reports -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">My Reports</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Reports created by you
            </p>
        </div>
        
        <div class="p-6">
            @if($myReports->isEmpty())
                <div class="text-center py-8">
                    <x-heroicon-o-chart-bar class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No reports yet</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Get started by creating your first report.
                    </p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($myReports as $report)
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <x-dynamic-component 
                                        :component="$report->getTypeIcon()" 
                                        class="h-6 w-6 text-{{ $report->getCategoryColor() }}-600" 
                                    />
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $report->name }}
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $report->description ?? 'No description' }}
                                    </p>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $report->getCategoryColor() }}-100 text-{{ $report->getCategoryColor() }}-800">
                                            {{ ucfirst($report->category) }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $report->getStatusColor() }}-100 text-{{ $report->getStatusColor() }}-800">
                                            {{ ucfirst($report->status) }}
                                        </span>
                                        @if($report->is_public)
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Public
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button 
                                    type="button" 
                                    wire:click="executeReport({{ $report->id }})"
                                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700"
                                >
                                    <x-heroicon-o-play class="h-4 w-4 mr-1" />
                                    Execute
                                </button>
                                <button 
                                    type="button" 
                                    wire:click="duplicateReport({{ $report->id }})"
                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    <x-heroicon-o-document-duplicate class="h-4 w-4 mr-1" />
                                    Duplicate
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Public Reports -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Public Reports</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Reports shared by other users
            </p>
        </div>
        
        <div class="p-6">
            @if($publicReports->isEmpty())
                <div class="text-center py-8">
                    <x-heroicon-o-eye class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No public reports</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        No reports have been shared publicly yet.
                    </p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($publicReports as $report)
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <x-dynamic-component 
                                        :component="$report->getTypeIcon()" 
                                        class="h-6 w-6 text-{{ $report->getCategoryColor() }}-600" 
                                    />
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $report->name }}
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        By {{ $report->user->name }} • {{ $report->created_at->diffForHumans() }}
                                    </p>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $report->getCategoryColor() }}-100 text-{{ $report->getCategoryColor() }}-800">
                                            {{ ucfirst($report->category) }}
                                        </span>
                                        @if($report->last_generated_at)
                                            <span class="text-xs text-gray-500">
                                                Last run: {{ $report->last_generated_at->diffForHumans() }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <button 
                                    type="button" 
                                    wire:click="executeReport({{ $report->id }})"
                                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700"
                                >
                                    <x-heroicon-o-play class="h-4 w-4 mr-1" />
                                    Execute
                                </button>
                                <button 
                                    type="button" 
                                    wire:click="duplicateReport({{ $report->id }})"
                                    class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50"
                                >
                                    <x-heroicon-o-document-duplicate class="h-4 w-4 mr-1" />
                                    Copy
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Available Data Sources -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Available Data Sources</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Models available for creating reports
            </p>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($availableModels as $modelClass => $modelInfo)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                            {{ $modelInfo['name'] }}
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                            {{ $modelClass }}
                        </p>
                        <div class="mb-3">
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Available Fields:</p>
                            <div class="flex flex-wrap gap-1">
                                @foreach($modelInfo['fields'] as $field)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        {{ $field }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Aggregatable Fields:</p>
                            <div class="flex flex-wrap gap-1">
                                @foreach($modelInfo['aggregatable'] as $field)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300">
                                        {{ $field }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>