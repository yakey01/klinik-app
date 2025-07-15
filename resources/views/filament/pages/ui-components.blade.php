<div class="space-y-8">
    <!-- Page Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">UI Component Library</h1>
        <p class="text-gray-600 dark:text-gray-400">
            Standardized UI components for consistent design across the admin interface.
        </p>
    </div>

    <!-- Statistics Cards -->
    <x-admin-card title="Statistics Cards" 
                  subtitle="Responsive stat cards for displaying key metrics"
                  icon="heroicon-o-chart-bar"
                  :collapsible="true">
        <x-responsive-grid cols="1" md-cols="2" lg-cols="4" gap="4">
            @foreach($demoStats as $stat)
                <x-stat-card 
                    :title="$stat['title']"
                    :value="$stat['value']"
                    :change="$stat['change']"
                    :changeType="$stat['changeType']"
                    :icon="$stat['icon']"
                    :color="$stat['color']"
                    :period="$stat['period']"
                />
            @endforeach
        </x-responsive-grid>
    </x-admin-card>

    <!-- Buttons -->
    <x-admin-card title="Buttons" 
                  subtitle="Various button styles and states"
                  icon="heroicon-o-cursor-arrow-rays"
                  :collapsible="true">
        <div class="space-y-6">
            <!-- Button Variants -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Button Variants</h3>
                <div class="flex flex-wrap gap-3">
                    <x-admin-button variant="primary">Primary</x-admin-button>
                    <x-admin-button variant="secondary">Secondary</x-admin-button>
                    <x-admin-button variant="success">Success</x-admin-button>
                    <x-admin-button variant="warning">Warning</x-admin-button>
                    <x-admin-button variant="danger">Danger</x-admin-button>
                    <x-admin-button variant="outline">Outline</x-admin-button>
                    <x-admin-button variant="ghost">Ghost</x-admin-button>
                </div>
            </div>

            <!-- Button Sizes -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Button Sizes</h3>
                <div class="flex flex-wrap items-center gap-3">
                    <x-admin-button size="xs">Extra Small</x-admin-button>
                    <x-admin-button size="sm">Small</x-admin-button>
                    <x-admin-button size="default">Default</x-admin-button>
                    <x-admin-button size="lg">Large</x-admin-button>
                    <x-admin-button size="xl">Extra Large</x-admin-button>
                </div>
            </div>

            <!-- Button States -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Button States</h3>
                <div class="flex flex-wrap gap-3">
                    <x-admin-button 
                        icon="heroicon-o-plus" 
                        wire:click="demoAction">
                        With Icon
                    </x-admin-button>
                    <x-admin-button 
                        icon="heroicon-o-arrow-right" 
                        icon-position="right">
                        Icon Right
                    </x-admin-button>
                    <x-admin-button 
                        :loading="true"
                        wire:click="demoLoadingAction">
                        Loading State
                    </x-admin-button>
                    <x-admin-button 
                        :disabled="true">
                        Disabled
                    </x-admin-button>
                    <x-admin-button 
                        :full-width="true"
                        variant="outline">
                        Full Width
                    </x-admin-button>
                </div>
            </div>
        </div>
    </x-admin-card>

    <!-- Badges -->
    <x-admin-card title="Badges" 
                  subtitle="Labels and status indicators"
                  icon="heroicon-o-tag"
                  :collapsible="true">
        <div class="space-y-6">
            <!-- Badge Variants -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Badge Variants</h3>
                <div class="flex flex-wrap gap-3">
                    <x-admin-badge variant="primary">Primary</x-admin-badge>
                    <x-admin-badge variant="success">Success</x-admin-badge>
                    <x-admin-badge variant="warning">Warning</x-admin-badge>
                    <x-admin-badge variant="error">Error</x-admin-badge>
                    <x-admin-badge variant="info">Info</x-admin-badge>
                    <x-admin-badge variant="purple">Purple</x-admin-badge>
                    <x-admin-badge variant="indigo">Indigo</x-admin-badge>
                    <x-admin-badge variant="outline">Outline</x-admin-badge>
                </div>
            </div>

            <!-- Badge Sizes -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Badge Sizes</h3>
                <div class="flex flex-wrap items-center gap-3">
                    <x-admin-badge size="sm">Small</x-admin-badge>
                    <x-admin-badge size="default">Default</x-admin-badge>
                    <x-admin-badge size="lg">Large</x-admin-badge>
                </div>
            </div>

            <!-- Badge Features -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Badge Features</h3>
                <div class="flex flex-wrap gap-3">
                    <x-admin-badge 
                        icon="heroicon-o-check"
                        variant="success">
                        With Icon
                    </x-admin-badge>
                    <x-admin-badge 
                        :pill="true"
                        variant="primary">
                        Pill Shape
                    </x-admin-badge>
                    <x-admin-badge 
                        :removable="true"
                        variant="warning">
                        Removable
                    </x-admin-badge>
                </div>
            </div>
        </div>
    </x-admin-card>

    <!-- Alerts -->
    <x-admin-card title="Alerts" 
                  subtitle="Notification and alert messages"
                  icon="heroicon-o-bell"
                  :collapsible="true">
        <div class="space-y-4">
            @foreach($demoNotifications as $notification)
                <x-admin-alert 
                    :type="$notification['type']"
                    :title="$notification['title']"
                    :dismissible="true"
                    action="showNotification('{{ $notification['type'] }}')"
                    action-label="Show">
                    {{ $notification['message'] }}
                </x-admin-alert>
            @endforeach
        </div>
    </x-admin-card>

    <!-- Progress Bars -->
    <x-admin-card title="Progress Bars" 
                  subtitle="Progress indicators and loading states"
                  icon="heroicon-o-chart-bar-square"
                  :collapsible="true">
        <div class="space-y-6">
            <!-- Basic Progress -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Basic Progress</h3>
                <div class="space-y-4">
                    @foreach($demoProgress as $progress)
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $progress['label'] }}
                                </span>
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $progress['value'] }}%
                                </span>
                            </div>
                            <x-progress-bar 
                                :value="$progress['value']"
                                :color="$progress['color']"
                            />
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Progress Sizes -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Progress Sizes</h3>
                <div class="space-y-4">
                    <x-progress-bar value="75" size="sm" color="blue" show-label="true" label-position="right">
                        Small
                    </x-progress-bar>
                    <x-progress-bar value="60" size="default" color="green" show-label="true" label-position="right">
                        Default
                    </x-progress-bar>
                    <x-progress-bar value="45" size="lg" color="yellow" show-label="true" label-position="right">
                        Large
                    </x-progress-bar>
                    <x-progress-bar value="90" size="xl" color="red" show-label="true" label-position="right">
                        Extra Large
                    </x-progress-bar>
                </div>
            </div>

            <!-- Progress Features -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Progress Features</h3>
                <div class="space-y-4">
                    <x-progress-bar value="65" color="purple" striped="true" animated="true" show-label="true" label-position="top">
                        Striped & Animated
                    </x-progress-bar>
                    <x-progress-bar value="80" color="indigo" show-label="true" label-position="bottom">
                        With Bottom Label
                    </x-progress-bar>
                </div>
            </div>
        </div>
    </x-admin-card>

    <!-- Responsive Grid -->
    <x-admin-card title="Responsive Grid System" 
                  subtitle="Flexible grid layouts that adapt to screen size"
                  icon="heroicon-o-squares-2x2"
                  :collapsible="true">
        <div class="space-y-6">
            <!-- Grid Examples -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Grid Examples</h3>
                
                <!-- 2 Column Grid -->
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">2 Column Grid (md:2)</h4>
                    <x-responsive-grid cols="1" md-cols="2" gap="4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                            <div class="text-sm font-medium text-blue-900 dark:text-blue-100">Item 1</div>
                        </div>
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                            <div class="text-sm font-medium text-blue-900 dark:text-blue-100">Item 2</div>
                        </div>
                    </x-responsive-grid>
                </div>

                <!-- 3 Column Grid -->
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">3 Column Grid (md:2, lg:3)</h4>
                    <x-responsive-grid cols="1" md-cols="2" lg-cols="3" gap="4">
                        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                            <div class="text-sm font-medium text-green-900 dark:text-green-100">Item 1</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                            <div class="text-sm font-medium text-green-900 dark:text-green-100">Item 2</div>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
                            <div class="text-sm font-medium text-green-900 dark:text-green-100">Item 3</div>
                        </div>
                    </x-responsive-grid>
                </div>

                <!-- 4 Column Grid -->
                <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">4 Column Grid (sm:2, md:3, lg:4)</h4>
                    <x-responsive-grid cols="1" sm-cols="2" md-cols="3" lg-cols="4" gap="4">
                        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                            <div class="text-sm font-medium text-purple-900 dark:text-purple-100">Item 1</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                            <div class="text-sm font-medium text-purple-900 dark:text-purple-100">Item 2</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                            <div class="text-sm font-medium text-purple-900 dark:text-purple-100">Item 3</div>
                        </div>
                        <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg border border-purple-200 dark:border-purple-800">
                            <div class="text-sm font-medium text-purple-900 dark:text-purple-100">Item 4</div>
                        </div>
                    </x-responsive-grid>
                </div>

                <!-- Auto-fit Grid -->
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Auto-fit Grid (min-width: 200px)</h4>
                    <x-responsive-grid :auto-fit="true" min-width="200px" gap="4">
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                            <div class="text-sm font-medium text-yellow-900 dark:text-yellow-100">Auto Item 1</div>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                            <div class="text-sm font-medium text-yellow-900 dark:text-yellow-100">Auto Item 2</div>
                        </div>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
                            <div class="text-sm font-medium text-yellow-900 dark:text-yellow-100">Auto Item 3</div>
                        </div>
                    </x-responsive-grid>
                </div>
            </div>
        </div>
    </x-admin-card>

    <!-- Typography -->
    <x-admin-card title="Typography" 
                  subtitle="Standardized text styles and hierarchy"
                  icon="heroicon-o-document-text"
                  :collapsible="true">
        <div class="space-y-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Heading 1</h1>
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-2">Heading 2</h2>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Heading 3</h3>
                <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Heading 4</h4>
                <p class="text-base text-gray-600 dark:text-gray-400 mb-2">
                    This is body text. Lorem ipsum dolor sit amet, consectetur adipiscing elit. 
                    Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-500 mb-2">
                    This is small text for captions and helper text.
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-600">
                    This is extra small text for timestamps and metadata.
                </p>
            </div>
        </div>
    </x-admin-card>

    <!-- CSS Classes Reference -->
    <x-admin-card title="CSS Classes Reference" 
                  subtitle="Available utility classes for consistent styling"
                  icon="heroicon-o-code-bracket"
                  :collapsible="true"
                  :default-collapsed="true">
        <div class="space-y-6">
            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Grid Classes</h3>
                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <div><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">.admin-grid-1</code> - Single column</div>
                    <div><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">.admin-grid-2</code> - 1 col → 2 cols (md)</div>
                    <div><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">.admin-grid-3</code> - 1 col → 2 cols (md) → 3 cols (lg)</div>
                    <div><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">.admin-grid-4</code> - 1 col → 2 cols (md) → 3 cols (lg) → 4 cols (xl)</div>
                    <div><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">.admin-grid-auto</code> - Auto-fit with min-width 280px</div>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Button Classes</h3>
                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <div><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">.admin-btn-primary</code> - Primary button style</div>
                    <div><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">.admin-btn-secondary</code> - Secondary button style</div>
                    <div><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">.admin-btn-outline</code> - Outline button style</div>
                    <div><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">.admin-btn-ghost</code> - Ghost button style</div>
                    <div><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">.admin-btn-sm</code> - Small button size</div>
                    <div><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">.admin-btn-lg</code> - Large button size</div>
                </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-900 p-4 rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Utility Classes</h3>
                <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <div><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">.admin-hide-mobile</code> - Hide on mobile devices</div>
                    <div><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">.admin-hide-desktop</code> - Hide on desktop</div>
                    <div><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">.admin-loading</code> - Loading animation</div>
                    <div><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">.admin-scrollbar</code> - Custom scrollbar</div>
                    <div><code class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">.admin-fade-in</code> - Fade in animation</div>
                </div>
            </div>
        </div>
    </x-admin-card>
</div>

@push('scripts')
<script src="{{ asset('js/admin-responsive.js') }}"></script>
@endpush

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin-components.css') }}">
@endpush