<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Email (SMTP) Settings</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('settings.email.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="smtp_host" :value="__('SMTP Host')" />
                            <x-text-input id="smtp_host" name="smtp_host" type="text" class="mt-1 block w-full" :value="old('smtp_host', $smtp_host)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('smtp_host')" />
                        </div>

                        <div>
                            <x-input-label for="smtp_port" :value="__('SMTP Port')" />
                            <x-text-input id="smtp_port" name="smtp_port" type="number" class="mt-1 block w-full" :value="old('smtp_port', $smtp_port)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('smtp_port')" />
                        </div>

                        <div>
                            <x-input-label for="smtp_username" :value="__('SMTP Username')" />
                            <x-text-input id="smtp_username" name="smtp_username" type="text" class="mt-1 block w-full" :value="old('smtp_username', $smtp_username)" />
                            <x-input-error class="mt-2" :messages="$errors->get('smtp_username')" />
                        </div>

                        <div>
                            <x-input-label for="smtp_password" :value="__('SMTP Password')" />
                            <x-text-input id="smtp_password" name="smtp_password" type="password" class="mt-1 block w-full" value="" autocomplete="new-password" />
                            <p class="mt-1 text-xs text-gray-600">
                                Leave blank to keep the saved password.
                                @if($has_password)
                                    (A password is already saved.)
                                @endif
                            </p>
                            <x-input-error class="mt-2" :messages="$errors->get('smtp_password')" />
                        </div>

                        <div>
                            <x-input-label for="smtp_encryption" :value="__('Encryption')" />
                            <select id="smtp_encryption" name="smtp_encryption" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="" {{ old('smtp_encryption', $smtp_encryption) === '' ? 'selected' : '' }}>None</option>
                                <option value="tls" {{ old('smtp_encryption', $smtp_encryption) === 'tls' ? 'selected' : '' }}>TLS</option>
                                <option value="ssl" {{ old('smtp_encryption', $smtp_encryption) === 'ssl' ? 'selected' : '' }}>SSL</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('smtp_encryption')" />
                        </div>

                        <div>
                            <x-input-label for="from_address" :value="__('From Address (optional)')" />
                            <x-text-input id="from_address" name="from_address" type="email" class="mt-1 block w-full" :value="old('from_address', $from_address)" />
                            <x-input-error class="mt-2" :messages="$errors->get('from_address')" />
                        </div>

                        <div>
                            <x-input-label for="from_name" :value="__('From Name (optional)')" />
                            <x-text-input id="from_name" name="from_name" type="text" class="mt-1 block w-full" :value="old('from_name', $from_name)" />
                            <x-input-error class="mt-2" :messages="$errors->get('from_name')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Save</x-primary-button>
                        </div>
                    </form>

                    <div class="mt-10 border-t pt-8">
                        <h3 class="text-base font-semibold text-gray-900">Send Test Email</h3>
                        <p class="mt-1 text-sm text-gray-600">Sends a test email using the saved SMTP settings.</p>

                        <form method="POST" action="{{ route('settings.email.test') }}" class="mt-6 space-y-4">
                            @csrf

                            <div>
                                <x-input-label for="test_to" :value="__('To Email')" />
                                <x-text-input id="test_to" name="test_to" type="email" class="mt-1 block w-full" :value="old('test_to')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('test_to')" />
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>Send Test</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
