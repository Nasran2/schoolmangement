<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Promotion Settings</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('settings.promotion.update') }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="auto_enabled" value="0" />
                                <input type="checkbox" name="auto_enabled" value="1" class="rounded border-gray-300" {{ old('auto_enabled', $auto_enabled) === '1' ? 'checked' : '' }}>
                                <span class="text-sm text-gray-800">Enable Auto Promotion</span>
                            </label>
                            <x-input-error class="mt-2" :messages="$errors->get('auto_enabled')" />
                        </div>

                        <div>
                            <x-input-label :value="__('Promotion Date')" />
                            <div class="mt-1 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <select name="month" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Select month</option>
                                        @php
                                            $months = [1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'];
                                        @endphp
                                        @foreach($months as $value => $label)
                                            <option value="{{ $value }}" {{ (int) old('month', $month) === (int) $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <select name="day" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Select day</option>
                                        @for($d = 1; $d <= 31; $d++)
                                            <option value="{{ $d }}" {{ (int) old('day', $day) === $d ? 'selected' : '' }}>{{ str_pad((string) $d, 2, '0', STR_PAD_LEFT) }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-gray-600">On this month/day each year, the system will auto-promote students once.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('month')" />
                            <x-input-error class="mt-2" :messages="$errors->get('day')" />
                        </div>

                        <div class="text-xs text-gray-600">Last auto run year: {{ $last_year_run ?: '—' }}</div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Save</x-primary-button>
                        </div>
                    </form>

                    <div class="mt-8 border-t pt-6" x-data="{}">
                        <h3 class="mb-4 text-sm font-semibold text-gray-700">Manual Promotion Controls</h3>
                        <div class="flex flex-wrap items-center gap-3">
                            @can('students.promote')
                                <form id="promote-all-form" method="POST" action="{{ route('students.promote') }}">
                                    @csrf
                                </form>
                                <x-primary-button type="button" x-on:click="$dispatch('open-modal', 'confirm-promote-all')">PROMOTE ALL</x-primary-button>
                            @endcan

                            @can('students.demote')
                                <form id="demote-all-form" method="POST" action="{{ route('students.demote') }}">
                                    @csrf
                                </form>
                                <x-danger-button type="button" x-on:click="$dispatch('open-modal', 'confirm-demote-all')">DEMOTE ALL</x-danger-button>
                            @endcan
                        </div>

                        @can('students.promote')
                            <x-modal name="confirm-promote-all" maxWidth="md">
                                <div class="p-6">
                                    <h2 class="text-lg font-medium text-gray-900">Confirm Promotion</h2>
                                    <p class="mt-2 text-sm text-gray-600">Promote all students? This will advance each to the next class level.</p>

                                    <div class="mt-6 flex justify-end gap-3">
                                        <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                        <x-primary-button x-on:click="document.getElementById('promote-all-form').submit(); $dispatch('close')">Yes, Promote</x-primary-button>
                                    </div>
                                </div>
                            </x-modal>
                        @endcan

                        @can('students.demote')
                            <x-modal name="confirm-demote-all" maxWidth="md">
                                <div class="p-6">
                                    <h2 class="text-lg font-medium text-gray-900">Confirm Demotion</h2>
                                    <p class="mt-2 text-sm text-gray-600">Demote all students? This will move each back one class level.</p>

                                    <div class="mt-6 flex justify-end gap-3">
                                        <x-secondary-button x-on:click="$dispatch('close')">Cancel</x-secondary-button>
                                        <x-danger-button x-on:click="document.getElementById('demote-all-form').submit(); $dispatch('close')">Yes, Demote</x-danger-button>
                                    </div>
                                </div>
                            </x-modal>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
