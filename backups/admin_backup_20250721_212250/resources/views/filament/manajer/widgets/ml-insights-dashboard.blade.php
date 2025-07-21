<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                ML Insights & Predictive Analytics
            </div>
        </x-slot>

        <div class="space-y-6">
            <!-- Quick Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Patient Flow Card -->
                <div class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-blue-800">Patient Flow</h3>
                        <div class="flex items-center gap-1">
                            @if($this->getViewData()['quickSummary']['patient_flow']['trend'] === 'increasing')
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                                </svg>
                            @endif
                            <span class="text-xs text-blue-600">{{ $this->getViewData()['quickSummary']['patient_flow']['confidence'] }}% confidence</span>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-2xl font-bold text-blue-900">
                            +{{ $this->getViewData()['quickSummary']['patient_flow']['change_percentage'] }}%
                        </div>
                        <div class="text-xs text-blue-700">
                            Next peak: {{ $this->getViewData()['quickSummary']['patient_flow']['next_peak'] }}
                        </div>
                    </div>
                </div>

                <!-- Revenue Forecast Card -->
                <div class="bg-gradient-to-r from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-green-800">Revenue Forecast</h3>
                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            <span class="text-xs text-green-600">{{ $this->getViewData()['quickSummary']['revenue_forecast']['confidence'] }}% confidence</span>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-2xl font-bold text-green-900">
                            +{{ $this->getViewData()['quickSummary']['revenue_forecast']['gfi-grid fi-grid-cols-autoth_rate'] }}%
                        </div>
                        <div class="text-xs text-green-700">
                            Next month: {{ $this->getViewData()['quickSummary']['revenue_forecast']['next_month'] }}
                        </div>
                    </div>
                </div>

                <!-- Health Trends Card -->
                <div class="bg-gradient-to-r from-orange-50 to-orange-100 rounded-lg p-4 border border-orange-200">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-orange-800">Health Trends</h3>
                        <div class="flex items-center gap-1">
                            <div class="w-2 h-2 rounded-full {{ $this->getViewData()['quickSummary']['health_trends']['risk_level'] === 'medium' ? 'bg-yellow-400' : 'bg-green-400' }}"></div>
                            <span class="text-xs text-orange-600">{{ ucfirst($this->getViewData()['quickSummary']['health_trends']['risk_level']) }} risk</span>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-2xl font-bold text-orange-900">
                            {{ $this->getViewData()['quickSummary']['health_trends']['emerging_patterns'] }} patterns
                        </div>
                        <div class="text-xs text-orange-700">
                            {{ $this->getViewData()['quickSummary']['health_trends']['active_alerts'] }} active alerts
                        </div>
                    </div>
                </div>

                <!-- Resource Efficiency Card -->
                <div class="bg-gradient-to-r from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-sm font-medium text-purple-800">Resource Efficiency</h3>
                        <div class="flex items-center gap-1">
                            <div class="w-8 h-2 bg-purple-200 rounded-full overflow-hidden">
                                <div class="h-full bg-purple-500 rounded-full" style="width: {{ $this->getViewData()['quickSummary']['resource_efficiency']['utilization_score'] }}%"></div>
                            </div>
                            <span class="text-xs text-purple-600">{{ $this->getViewData()['quickSummary']['resource_efficiency']['utilization_score'] }}%</span>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-2xl font-bold text-purple-900">
                            {{ $this->getViewData()['quickSummary']['resource_efficiency']['optimization_potential'] }}% potential
                        </div>
                        <div class="text-xs text-purple-700">
                            {{ $this->getViewData()['quickSummary']['resource_efficiency']['maintenance_due'] }} items due
                        </div>
                    </div>
                </div>
            </div>

            <!-- Predictive Alerts -->
            <div class="bg-white rounded-lg border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        Predictive Alerts
                    </h3>
                </div>
                <div class="p-4 space-y-3">
                    @foreach($this->getViewData()['alerts'] as $alert)
                        <div class="flex items-start gap-3 p-3 rounded-lg {{ $alert['severity'] === 'high' ? 'bg-red-50 border border-red-200' : ($alert['severity'] === 'medium' ? 'bg-yellow-50 border border-yellow-200' : 'bg-blue-50 border border-blue-200') }}">
                            <div class="flex-shrink-0">
                                @if($alert['severity'] === 'high')
                                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                @elseif($alert['severity'] === 'medium')
                                    <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-medium {{ $alert['severity'] === 'high' ? 'text-red-800' : ($alert['severity'] === 'medium' ? 'text-yellow-800' : 'text-blue-800') }}">
                                        {{ $alert['title'] }}
                                    </h4>
                                    <span class="text-xs {{ $alert['severity'] === 'high' ? 'text-red-600' : ($alert['severity'] === 'medium' ? 'text-yellow-600' : 'text-blue-600') }}">
                                        {{ $alert['probability'] }}% probability
                                    </span>
                                </div>
                                <p class="text-sm {{ $alert['severity'] === 'high' ? 'text-red-700' : ($alert['severity'] === 'medium' ? 'text-yellow-700' : 'text-blue-700') }} mt-1">
                                    {{ $alert['message'] }}
                                </p>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    @foreach($alert['actions'] as $action)
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs {{ $alert['severity'] === 'high' ? 'bg-red-100 text-red-700' : ($alert['severity'] === 'medium' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700') }}">
                                            {{ $action }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Recommendations -->
            <div class="bg-white rounded-lg border border-gray-200">
                <div class="p-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                        </svg>
                        ML-Powered Recommendations
                    </h3>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($this->getViewData()['recommendations'] as $recommendation)
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <div class="flex items-start justify-between mb-2">
                                    <h4 class="text-sm font-medium text-gray-900">{{ $recommendation['title'] }}</h4>
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs {{ $recommendation['category'] === 'operations' ? 'bg-blue-100 text-blue-700' : ($recommendation['category'] === 'revenue' ? 'bg-green-100 text-green-700' : ($recommendation['category'] === 'health' ? 'bg-red-100 text-red-700' : 'bg-purple-100 text-purple-700')) }}">
                                        {{ ucfirst($recommendation['category']) }}
                                    </span>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-600">Impact:</span>
                                        <span class="font-medium {{ $recommendation['impact'] === 'High' ? 'text-green-600' : ($recommendation['impact'] === 'Medium' ? 'text-yellow-600' : 'text-gray-600') }}">{{ $recommendation['impact'] }}</span>
                                    </div>
                                    <div class="flex justify-between text-xs">
                                        <span class="text-gray-600">Effort:</span>
                                        <span class="font-medium {{ $recommendation['effort'] === 'Low' ? 'text-green-600' : ($recommendation['effort'] === 'Medium' ? 'text-yellow-600' : 'text-red-600') }}">{{ $recommendation['effort'] }}</span>
                                    </div>
                                    <div class="text-xs text-gray-600 pt-1 border-t border-gray-200">
                                        ROI: <span class="font-medium text-gray-900">{{ $recommendation['roi'] }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>