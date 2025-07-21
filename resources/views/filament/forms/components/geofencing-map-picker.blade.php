<div class="simple-map-test p-4 bg-gray-100 border border-gray-300 rounded-lg">
    <h3 class="text-lg font-semibold mb-2">🗺️ Map Component Test</h3>
    <p class="text-sm text-gray-600 mb-4">This is a simplified version to test for multiple root elements error.</p>
    
    <div class="grid grid-cols-2 gap-4 mb-4">
        <input type="hidden" name="latitude" value="-7.89946200" />
        <input type="hidden" name="longitude" value="111.96239900" />
        
        <div>
            <label class="block text-sm font-medium text-gray-700">Latitude</label>
            <div class="text-sm text-gray-900">-7.89946200</div>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700">Longitude</label>
            <div class="text-sm text-gray-900">111.96239900</div>
        </div>
    </div>
    
    <div class="bg-blue-50 border border-blue-200 rounded p-4" style="height: 200px;">
        <div class="flex items-center justify-center h-full">
            <div class="text-center">
                <div class="text-2xl mb-2">📍</div>
                <div class="text-sm text-blue-600">Simplified Map Placeholder</div>
                <div class="text-xs text-gray-500 mt-1">Single root element test</div>
            </div>
        </div>
    </div>
    
    <div class="mt-4 text-xs text-gray-500">
        Component rendered successfully with single root element.
    </div>
</div>