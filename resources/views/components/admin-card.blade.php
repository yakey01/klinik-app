<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 {{ $collapsible ? 'overflow-hidden' : '' }}" 
     @if($collapsible) x-data="{ collapsed: {{ $defaultCollapsed ? 'true' : 'false' }} }" @endif>
    
    <!-- Header -->
    <div class="flex items-center justify-between {{ $getSizeClasses() }} {{ $collapsible ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
        <div class="flex items-center space-x-3">
            @if($icon)
                <div class="flex-shrink-0">
                    <div class="p-2 rounded-full {{ $getIconColorClasses() }}">
                        <x-dynamic-component :component="$icon" class="h-5 w-5" />
                    </div>
                </div>
            @endif
            
            <div class="flex-1 min-w-0">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                    {{ $title }}
                </h3>
                @if($subtitle)
                    <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                        {{ $subtitle }}
                    </p>
                @endif
            </div>
        </div>
        
        <div class="flex items-center space-x-2">
            @if($loading)
                <div class="animate-spin rounded-full h-4 w-4 border-2 border-blue-500 border-t-transparent"></div>
            @endif
            
            @if($action && $actionLabel)
                <button type="button" 
                        onclick="{{ $action }}"
                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150">
                    {{ $actionLabel }}
                </button>
            @endif
            
            @if($collapsible)
                <button type="button" 
                        @click="collapsed = !collapsed"
                        class="p-1 rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-150">
                    <x-heroicon-o-chevron-down class="h-4 w-4 transform transition-transform duration-200" 
                                               :class="{ 'rotate-180': !collapsed }" />
                </button>
            @endif
        </div>
    </div>
    
    <!-- Content -->
    <div @if($collapsible) x-show="!collapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-y-95" x-transition:enter-end="opacity-100 transform scale-y-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 transform scale-y-100" x-transition:leave-end="opacity-0 transform scale-y-95" @endif>
        <div class="{{ $getSizeClasses() }} {{ $collapsible ? 'pt-0' : '' }}">
            {{ $slot }}
        </div>
        
        @if($footer)
            <div class="px-6 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $footer }}
                </div>
            </div>
        @endif
    </div>
</div>