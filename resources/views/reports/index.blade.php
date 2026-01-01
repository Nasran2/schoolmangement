<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reports') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <a href="{{ route('reports.revenue') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Revenue Report</div>
                            <div class="text-sm text-gray-600">Filter by date range and category.</div>
                        </a>
                        <a href="{{ route('reports.expense') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Expense Report</div>
                            <div class="text-sm text-gray-600">Filter by date range and category.</div>
                        </a>
                        <a href="{{ route('reports.financial') }}" class="block border rounded-lg p-4 hover:bg-gray-50">
                            <div class="font-semibold">Financial Summary</div>
                            <div class="text-sm text-gray-600">Revenue vs expenses and net profit.</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
