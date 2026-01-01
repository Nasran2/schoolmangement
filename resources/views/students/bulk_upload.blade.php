<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bulk Upload Students</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('students.bulk.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        <div>
                            <x-input-label for="csv" :value="__('CSV File')" />
                            <input id="csv" name="csv" type="file" accept=".csv,text/csv" class="mt-1 block w-full" required />
                            <x-input-error class="mt-2" :messages="$errors->get('csv')" />
                        </div>

                        <div class="text-xs text-gray-600">
                            Headers: admission_number,name,phone,joining_date,address,guardian_name,guardian_phone,class_room_name,class_room_level,monthly_fee,due_amount,active
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Import</x-primary-button>
                            <a href="{{ route('students.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
