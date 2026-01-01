<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Printer Settings</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('settings.printer.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="slip_header" :value="__('Slip Header (HTML allowed)')" />
                            <textarea id="slip_header" name="slip_header" class="mt-1 block w-full rounded-md border-gray-300" rows="4">{{ old('slip_header', $slip_header) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('slip_header')" />
                        </div>

                        <div>
                            <x-input-label for="slip_footer" :value="__('Slip Footer (HTML allowed)')" />
                            <textarea id="slip_footer" name="slip_footer" class="mt-1 block w-full rounded-md border-gray-300" rows="4">{{ old('slip_footer', $slip_footer) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('slip_footer')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Save</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
