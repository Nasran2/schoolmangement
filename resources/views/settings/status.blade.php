<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('System Status') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-6">
                    <p class="text-sm text-gray-600">Quick validation of key integrations and settings.</p>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="rounded-lg border border-gray-200 p-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-900">Email (SMTP)</h3>
                                <span class="text-xs font-semibold {{ $smtp['configured'] ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $smtp['configured'] ? 'Configured' : 'Not Configured' }}
                                </span>
                            </div>

                            <div class="mt-3 text-sm text-gray-700">
                                <div>Last test: <span class="font-medium text-gray-900">{{ $smtp['last_tested_at'] ?: 'Never' }}</span></div>
                                @if($smtp['last_test_status'])
                                    <div>Status: <span class="font-medium {{ $smtp['last_test_status'] === 'ok' ? 'text-green-700' : 'text-red-700' }}">{{ strtoupper($smtp['last_test_status']) }}</span></div>
                                @endif
                                @if($smtp['last_test_status'] === 'failed' && $smtp['last_test_error'])
                                    <div class="mt-2 text-xs text-red-700">{{ $smtp['last_test_error'] }}</div>
                                @endif
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('settings.email.edit') }}" class="inline-flex items-center rounded-md bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100">Open SMTP Settings</a>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-900">SMS Gateway</h3>
                                <span class="text-xs font-semibold {{ $sms['configured'] ? 'text-green-700' : 'text-red-700' }}">
                                    {{ $sms['configured'] ? 'Configured' : 'Not Configured' }}
                                </span>
                            </div>

                            <div class="mt-3 text-sm text-gray-700">
                                <div>Last test: <span class="font-medium text-gray-900">{{ $sms['last_tested_at'] ?: 'Never' }}</span></div>
                                @if($sms['last_test_status'])
                                    <div>Status: <span class="font-medium {{ $sms['last_test_status'] === 'ok' ? 'text-green-700' : 'text-red-700' }}">{{ strtoupper($sms['last_test_status']) }}</span></div>
                                @endif
                                @if($sms['last_test_status'] === 'failed' && $sms['last_test_error'])
                                    <div class="mt-2 text-xs text-red-700">{{ $sms['last_test_error'] }}</div>
                                @endif
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('settings.sms.edit') }}" class="inline-flex items-center rounded-md bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100">Open SMS Settings</a>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 p-4">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-gray-900">Printer Slip</h3>
                                <span class="text-xs font-semibold {{ $printer['configured'] ? 'text-green-700' : 'text-gray-700' }}">
                                    {{ $printer['configured'] ? 'Customized' : 'Default' }}
                                </span>
                            </div>

                            <div class="mt-3 text-sm text-gray-700">
                                <div>Last update: <span class="font-medium text-gray-900">{{ $printer['last_updated_at'] ?: 'Never' }}</span></div>
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('settings.printer.edit') }}" class="inline-flex items-center rounded-md bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100">Open Printer Settings</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
