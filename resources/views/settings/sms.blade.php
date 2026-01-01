<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">SMS Settings</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('settings.sms.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="gateway_url" :value="__('Gateway URL')" />
                            <x-text-input id="gateway_url" name="gateway_url" type="url" class="mt-1 block w-full" :value="old('gateway_url', $gateway_url)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('gateway_url')" />
                        </div>

                        <div>
                            <x-input-label for="gateway_token" :value="__('Gateway Token')" />
                            <x-text-input id="gateway_token" name="gateway_token" type="text" class="mt-1 block w-full" :value="old('gateway_token', $gateway_token)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('gateway_token')" />
                        </div>

                        <div>
                            <x-input-label for="sender" :value="__('Sender (optional)')" />
                            <x-text-input id="sender" name="sender" type="text" class="mt-1 block w-full" :value="old('sender', $sender)" />
                            <x-input-error class="mt-2" :messages="$errors->get('sender')" />
                        </div>

                        <div>
                            <x-input-label for="due_template" :value="__('Due Message Template')" />
                            <textarea id="due_template" name="due_template" class="mt-1 block w-full rounded-md border-gray-300" rows="4">{{ old('due_template', $due_template) }}</textarea>
                            <p class="mt-1 text-xs text-gray-600">Available placeholders: {name}, {amount}, {date}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('due_template')" />
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
