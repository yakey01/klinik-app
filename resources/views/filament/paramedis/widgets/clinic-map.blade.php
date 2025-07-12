<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-x-3">
                <x-heroicon-o-map class="h-6 w-6 text-blue-500" />
                <span class="text-lg font-semibold">Clinic Location Map</span>
            </div>
        </x-slot>

        <div class="space-y-4">
            {{-- Map Information --}}
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-center gap-2 mb-2">
                    <x-heroicon-o-building-office-2 class="h-5 w-5 text-blue-500" />
                    <h4 class="font-medium text-blue-800 dark:text-blue-300">Klinik Dokterku</h4>
                </div>
                <div class="text-sm text-blue-600 dark:text-blue-400 space-y-1">
                    <div>📍 Coordinates: {{ $clinicLat }}, {{ $clinicLng }}</div>
                    <div>📏 Attendance Radius: {{ $clinicRadius }} meters</div>
                    <div>💡 Tip: Use "My location" button to see your distance from the clinic</div>
                </div>
            </div>

            {{-- Interactive Map --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                {{ $this->form }}
            </div>

            {{-- Instructions --}}
            <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <div class="text-sm text-green-800 dark:text-green-300">
                    <div class="font-medium mb-1">📱 How to use for attendance:</div>
                    <ul class="list-disc list-inside space-y-1 text-green-700 dark:text-green-400">
                        <li>Click "My location" button on the map to see your current position</li>
                        <li>Ensure you are within {{ $clinicRadius }}m of the clinic (green circle)</li>
                        <li>Use the Location Detection widget above to check exact distance</li>
                        <li>Only then you can check in/out for attendance</li>
                    </ul>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>