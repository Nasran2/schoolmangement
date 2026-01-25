<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bulk Upload Students</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-6 border-b pb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Step 1: Download Excel Template</h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Download the Excel template (.xlsx) which includes headers, a sample row, and dropdowns.
                            <br>Required fields are marked with *.
                            <br><strong>Defaults:</strong> Nationality is pre-filled as Sri Lankan and Student Active is always 1.
                            <br><strong>Class:</strong> Select from the dropdown (pulled from Class Rooms).
                            <br><strong>Monthly Fee:</strong> Auto-filled from the selected Class.
                        </p>
                        <a href="{{ route('students.bulk.template') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                            Download Template
                        </a>
                    </div>

                    <form method="POST" action="{{ route('students.bulk.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Step 2: Upload Files</h3>
                            <x-input-label for="csv" :value="__('Excel / CSV File')" />
                            <input id="csv" name="csv" type="file" accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv" class="mt-1 block w-full" required />
                            <x-input-error class="mt-2" :messages="$errors->get('csv')" />
                        </div>

                        <div class="text-xs text-gray-600">
                            Upload the filled CSV file. Maximum 50 students per upload recommended.
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Import Students</x-primary-button>
                            <a href="{{ route('students.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
