<div class="space-y-6">
    <!-- Statistics Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Import Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <x-heroicon-o-arrow-down-tray class="h-6 w-6 text-blue-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Imports</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $importStats['total_imports'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Export Stats -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                    <x-heroicon-o-arrow-up-tray class="h-6 w-6 text-green-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Exports</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $exportStats['total_exports'] ?? 0 }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Processing Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
                    <x-heroicon-o-clock class="h-6 w-6 text-yellow-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Processing</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ ($importStats['processing_imports'] ?? 0) + ($exportStats['processing_exports'] ?? 0) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                    <x-heroicon-o-chart-bar class="h-6 w-6 text-purple-600" />
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Success Rate</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($importStats['avg_success_rate'] ?? 0, 1) }}%
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Imports -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Imports</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Your latest data import operations
            </p>
        </div>
        
        <div class="p-6">
            @if($recentImports->isEmpty())
                <div class="text-center py-8">
                    <x-heroicon-o-arrow-down-tray class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No imports yet</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Get started by creating your first data import.
                    </p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($recentImports as $import)
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <x-dynamic-component 
                                        :component="$import->getSourceTypeIcon()" 
                                        class="h-6 w-6 text-gray-600 dark:text-gray-400" 
                                    />
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $import->name }}
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $import->description ?? 'No description' }}
                                    </p>
                                    <div class="flex items-center space-x-4 mt-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $import->getStatusColor() }}-100 text-{{ $import->getStatusColor() }}-800">
                                            {{ ucfirst($import->status) }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            {{ $import->source_type }} → {{ class_basename($import->target_model) }}
                                        </span>
                                        @if($import->processed_rows > 0)
                                            <span class="text-xs text-gray-500">
                                                {{ number_format($import->processed_rows) }} rows processed
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($import->status === 'pending')
                                    <button 
                                        type="button" 
                                        wire:click="previewImport({{ $import->id }})"
                                        class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        <x-heroicon-o-eye class="h-4 w-4 mr-1" />
                                        Preview
                                    </button>
                                    <button 
                                        type="button" 
                                        wire:click="executeImport({{ $import->id }})"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700"
                                    >
                                        <x-heroicon-o-play class="h-4 w-4 mr-1" />
                                        Execute
                                    </button>
                                @elseif($import->status === 'processing')
                                    <button 
                                        type="button" 
                                        wire:click="cancelOperation('import', {{ $import->id }})"
                                        class="inline-flex items-center px-3 py-1.5 border border-red-300 text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50"
                                    >
                                        <x-heroicon-o-x-mark class="h-4 w-4 mr-1" />
                                        Cancel
                                    </button>
                                    <div class="flex items-center">
                                        <div class="w-20 bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $import->progress_percentage }}%"></div>
                                        </div>
                                        <span class="ml-2 text-xs text-gray-500">{{ $import->progress_percentage }}%</span>
                                    </div>
                                @elseif($import->status === 'completed')
                                    <span class="text-xs text-green-600">
                                        {{ $import->getSuccessRate() }}% success rate
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Exports -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Exports</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Your latest data export operations
            </p>
        </div>
        
        <div class="p-6">
            @if($recentExports->isEmpty())
                <div class="text-center py-8">
                    <x-heroicon-o-arrow-up-tray class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No exports yet</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Get started by creating your first data export.
                    </p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($recentExports as $export)
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <x-dynamic-component 
                                        :component="$export->getExportFormatIcon()" 
                                        class="h-6 w-6 text-gray-600 dark:text-gray-400" 
                                    />
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $export->name }}
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $export->description ?? 'No description' }}
                                    </p>
                                    <div class="flex items-center space-x-4 mt-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $export->getStatusColor() }}-100 text-{{ $export->getStatusColor() }}-800">
                                            {{ ucfirst($export->status) }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            {{ class_basename($export->source_model) }} → {{ strtoupper($export->export_format) }}
                                        </span>
                                        @if($export->exported_rows > 0)
                                            <span class="text-xs text-gray-500">
                                                {{ number_format($export->exported_rows) }} rows exported
                                            </span>
                                        @endif
                                        @if($export->file_size)
                                            <span class="text-xs text-gray-500">
                                                {{ $export->getFormattedFileSize() }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($export->status === 'pending')
                                    <button 
                                        type="button" 
                                        wire:click="executeExport({{ $export->id }})"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-blue-600 hover:bg-blue-700"
                                    >
                                        <x-heroicon-o-play class="h-4 w-4 mr-1" />
                                        Execute
                                    </button>
                                @elseif($export->status === 'processing')
                                    <button 
                                        type="button" 
                                        wire:click="cancelOperation('export', {{ $export->id }})"
                                        class="inline-flex items-center px-3 py-1.5 border border-red-300 text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50"
                                    >
                                        <x-heroicon-o-x-mark class="h-4 w-4 mr-1" />
                                        Cancel
                                    </button>
                                    <div class="flex items-center">
                                        <div class="w-20 bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $export->progress_percentage }}%"></div>
                                        </div>
                                        <span class="ml-2 text-xs text-gray-500">{{ $export->progress_percentage }}%</span>
                                    </div>
                                @elseif($export->status === 'completed')
                                    @if($export->isDownloadable())
                                        <button 
                                            type="button" 
                                            wire:click="downloadExport({{ $export->id }})"
                                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700"
                                        >
                                            <x-heroicon-o-arrow-down-tray class="h-4 w-4 mr-1" />
                                            Download
                                        </button>
                                    @else
                                        <span class="text-xs text-red-600">
                                            File expired or unavailable
                                        </span>
                                    @endif
                                    @if($export->download_count > 0)
                                        <span class="text-xs text-gray-500">
                                            {{ $export->download_count }} downloads
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Recent Transformations -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Transformations</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Your latest data transformation operations
            </p>
        </div>
        
        <div class="p-6">
            @if($recentTransformations->isEmpty())
                <div class="text-center py-8">
                    <x-heroicon-o-sparkles class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No transformations yet</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Get started by creating your first data transformation.
                    </p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($recentTransformations as $transformation)
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <x-dynamic-component 
                                        :component="$transformation->getTransformationTypeIcon()" 
                                        class="h-6 w-6 text-{{ $transformation->getTransformationTypeColor() }}-600" 
                                    />
                                </div>
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $transformation->name }}
                                    </h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $transformation->description ?? 'No description' }}
                                    </p>
                                    <div class="flex items-center space-x-4 mt-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $transformation->getStatusColor() }}-100 text-{{ $transformation->getStatusColor() }}-800">
                                            {{ ucfirst($transformation->status) }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $transformation->getTransformationTypeColor() }}-100 text-{{ $transformation->getTransformationTypeColor() }}-800">
                                            {{ ucfirst($transformation->transformation_type) }}
                                        </span>
                                        @if($transformation->isDryRun())
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Dry Run
                                            </span>
                                        @endif
                                        @if($transformation->processed_records > 0)
                                            <span class="text-xs text-gray-500">
                                                {{ number_format($transformation->processed_records) }} records processed
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($transformation->status === 'processing')
                                    <button 
                                        type="button" 
                                        wire:click="cancelOperation('transformation', {{ $transformation->id }})"
                                        class="inline-flex items-center px-3 py-1.5 border border-red-300 text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50"
                                    >
                                        <x-heroicon-o-x-mark class="h-4 w-4 mr-1" />
                                        Cancel
                                    </button>
                                    <div class="flex items-center">
                                        <div class="w-20 bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $transformation->progress_percentage }}%"></div>
                                        </div>
                                        <span class="ml-2 text-xs text-gray-500">{{ $transformation->progress_percentage }}%</span>
                                    </div>
                                @elseif($transformation->status === 'completed')
                                    <span class="text-xs text-green-600">
                                        {{ $transformation->getSuccessRate() }}% success rate
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Performance Summary -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Performance Summary</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Overall data management statistics
            </p>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-600">
                        {{ number_format($importStats['total_rows_processed'] ?? 0) }}
                    </div>
                    <div class="text-sm text-gray-500">Total Rows Processed</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600">
                        {{ number_format($exportStats['total_rows_exported'] ?? 0) }}
                    </div>
                    <div class="text-sm text-gray-500">Total Rows Exported</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-purple-600">
                        {{ number_format($exportStats['total_downloads'] ?? 0) }}
                    </div>
                    <div class="text-sm text-gray-500">Total Downloads</div>
                </div>
            </div>
        </div>
    </div>
</div>