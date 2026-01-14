<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Revenue Categories</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('revenue.categories.store') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4" x-data="{ type: '{{ old('payment_type','monthly') }}' }">
                        @csrf
                        <div class="sm:col-span-2">
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="payment_type" :value="__('Payment Type')" />
                            <select id="payment_type" name="payment_type" class="mt-1 block w-full rounded-md border-gray-300" x-model="type">
                                <option value="monthly">Monthly</option>
                                <option value="2_months">Every 2 Months</option>
                                <option value="3_months">Every 3 Months</option>
                                <option value="6_months">Every 6 Months</option>
                                <option value="yearly">Yearly</option>
                                <option value="custom_months">Custom (Every N Months)</option>
                                <option value="one_time">One-time</option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('payment_type')" />

                            <div x-cloak x-show="type === 'custom_months'" class="mt-3">
                                <x-input-label for="interval_months" :value="__('Interval (months)')" />
                                <x-text-input id="interval_months" name="interval_months" type="number" min="1" max="24" class="mt-1 block w-full" :value="old('interval_months')" />
                                <x-input-error class="mt-2" :messages="$errors->get('interval_months')" />
                            </div>
                        </div>

                        <div class="flex items-end">
                            <x-primary-button>Create</x-primary-button>
                        </div>

                        <div class="sm:col-span-4">
                            <x-input-label for="description" :value="__('Description (optional)')" />
                            <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" />
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        <div class="sm:col-span-4">
                            <div class="flex items-center gap-2">
                                <input type="hidden" name="applies_to_all" value="0" />
                                <input id="applies_to_all" type="checkbox" name="applies_to_all" value="1" class="rounded border-gray-300" {{ old('applies_to_all', '1') === '1' ? 'checked' : '' }}>
                                <label for="applies_to_all" class="text-sm text-gray-800">Applies to all classes</label>
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('applies_to_all')" />

                            <div class="mt-3">
                                <div class="text-xs font-semibold text-gray-600">If not all, select applicable classes:</div>
                                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                    @foreach ($classRooms as $cr)
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                            <input type="checkbox" name="class_room_ids[]" value="{{ $cr->id }}" class="rounded border-gray-300" {{ in_array((string) $cr->id, array_map('strval', old('class_room_ids', []))) ? 'checked' : '' }}>
                                            <span>{{ $cr->level !== null ? ('Level '.$cr->level.' - ') : '' }}{{ $cr->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error class="mt-2" :messages="$errors->get('class_room_ids')" />
                            </div>
                        </div>
                    </form>

                    <div class="mt-8 divide-y rounded border">
                        <div class="grid grid-cols-12 px-4 py-2 text-xs font-semibold text-gray-600">
                            <div class="col-span-5">Name</div>
                            <div class="col-span-3">Type</div>
                            <div class="col-span-2">Applies</div>
                            <div class="col-span-2 text-right">Actions</div>
                        </div>
                        @forelse ($categories as $cat)
                            <div class="grid grid-cols-12 items-center px-4 py-3">
                                <div class="col-span-5 text-sm font-medium text-gray-900">{{ $cat->name }}</div>
                                <div class="col-span-3 text-sm text-gray-700">
                                    @php
                                        $type = (string) $cat->payment_type;
                                        $n = $cat->intervalMonths();
                                        $label = match ($type) {
                                            'monthly' => 'Monthly',
                                            '2_months' => 'Every 2 Months',
                                            '3_months' => 'Every 3 Months',
                                            '6_months' => 'Every 6 Months',
                                            'yearly' => 'Yearly',
                                            'custom_months' => $n ? ('Every '.$n.' Months') : 'Custom',
                                            'one_time' => 'One-time',
                                            default => $type,
                                        };
                                    @endphp
                                    {{ $label }}
                                </div>
                                <div class="col-span-2 text-sm text-gray-700">
                                    {{ $cat->applies_to_all ? 'All' : 'Selected' }}
                                    @if (! $cat->active)
                                        <span class="text-gray-400">· Disabled</span>
                                    @endif
                                </div>
                                <div class="col-span-2 flex justify-end gap-2">
                                    <a href="{{ route('revenue.categories.edit', $cat) }}" class="text-sm text-indigo-600 hover:underline">Edit</a>
                                    <span x-data="{ open:false }">
                                        <button type="button" class="text-sm text-red-600 hover:underline" x-on:click="open=true">Delete</button>
                                        <form x-ref="delForm" class="hidden" method="POST" action="{{ route('revenue.categories.destroy', $cat) }}">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-center justify-center">
                                            <div class="absolute inset-0 bg-black/40" x-on:click="open=false"></div>
                                            <div class="relative z-10 w-full max-w-sm rounded-md bg-white p-5 shadow-lg">
                                                <div class="text-sm font-semibold text-gray-800">Delete Category</div>
                                                <div class="mt-2 text-sm text-gray-600">Are you sure you want to delete this category?</div>
                                                <div class="mt-4 flex justify-end gap-2">
                                                    <button type="button" class="rounded-md border px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50" x-on:click="open=false">Cancel</button>
                                                    <button type="button" class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-red-700" x-on:click="$refs.delForm.submit()">Delete</button>
                                                </div>
                                            </div>
                                        </div>
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="px-4 py-3 text-sm text-gray-600">No revenue categories yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
