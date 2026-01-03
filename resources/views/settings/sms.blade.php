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

                    <div class="mt-8 border-t pt-6">
                        <h3 class="text-base font-semibold text-gray-900">Send Test SMS</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            @if($last_tested_at)
                                Last test: <span class="font-medium text-gray-900">{{ $last_tested_at }}</span>
                                @if($last_test_status)
                                    (<span class="font-medium {{ $last_test_status === 'ok' ? 'text-green-700' : 'text-red-700' }}">{{ strtoupper($last_test_status) }}</span>)
                                @endif
                            @else
                                No tests run yet.
                            @endif
                        </p>
                        @if($last_test_status === 'failed' && $last_test_error)
                            <p class="mt-2 text-sm text-red-700">{{ $last_test_error }}</p>
                        @endif

                        <form method="POST" action="{{ route('settings.sms.test') }}" class="mt-4 space-y-4">
                            @csrf

                            <div>
                                <x-input-label for="test_phone" :value="__('Phone Number')" />
                                <x-text-input id="test_phone" name="test_phone" type="text" class="mt-1 block w-full" :value="old('test_phone')" placeholder="e.g. 947XXXXXXXX" required />
                                <x-input-error class="mt-2" :messages="$errors->get('test_phone')" />
                            </div>

                            <div>
                                <x-input-label for="test_message" :value="__('Message (optional)')" />
                                <x-text-input id="test_message" name="test_message" type="text" class="mt-1 block w-full" :value="old('test_message')" placeholder="Leave blank to use default test message" />
                                <x-input-error class="mt-2" :messages="$errors->get('test_message')" />
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>Send Test SMS</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
