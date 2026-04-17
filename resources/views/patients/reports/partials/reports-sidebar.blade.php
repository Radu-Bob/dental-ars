<div class="bg-white p-6 rounded-xl shadow-lg space-y-4">
    <h2 class="text-xl font-bold text-gray-800 border-b pb-3 mb-3">Reports Navigation</h2>
    
    <p class="text-sm text-gray-600 mb-4">Jump directly to a specific report type.</p>

    <div class="space-y-2">
        <!-- Always visible "back" button
        <a href="{{ route('reports.index') }}"
           class="w-full text-left block text-sm bg-green-500 text-white font-medium py-2 px-3 rounded-lg hover:bg-green-600 transition duration-150">
            ← Back to Dashboard
        </a>
-->

        <!-- Dynamic report links + active highlighting -->
        @foreach ($reports as $report)
            <a href="{{ route($report['route']) }}" @class(["w-full text-left block text-sm font-medium py-2 px-3 rounded-lg transition duration-150","bg-clinic text-white shadow-md" => Route::currentRouteName() === $report['route'], 'bg-gray-100 text-gray-700 hover:bg-gray-200' => Route::currentRouteName() !== $report['route'],])>
                {{ $report['title'] }}
            </a>
        @endforeach
    </div>
    <!-- Optional: Link to main page reports -->
    <div class="pt-4 border-t mt-4">
        <a href="{{ route('reports.index') }}"
        class="w-full block text-center text-sm btn-clinic-grey text-white font-medium py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition duration-150 shadow-sm">
            ← Back to Reports
        </a>
    </div>

    <!-- Optional: future custom report button -->
    

    
</div>