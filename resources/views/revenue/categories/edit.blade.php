<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Revenue Category</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('revenue.categories.update', $category) }}" class="space-y-6" x-data="{ type: '{{ old('payment_type', $category->payment_type) }}' }">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $category->name)" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="payment_type" :value="__('Payment Type')" />
                            <select id="payment_type" name="payment_type" class="mt-1 block w-full rounded-md border-gray-300" x-model="type">
                                @php
                                    $types = [
                                        'monthly' => 'Monthly',
                                        '2_months' => 'Every 2 Months',
                                        '3_months' => 'Every 3 Months',
                                        '6_months' => 'Every 6 Months',
                                        'yearly' => 'Yearly',
                                        'custom_months' => 'Custom (Every N Months)',
                                        'one_time' => 'One-time',
                                    ];
                                @endphp
                                @foreach ($types as $val => $label)
                                    <option value="{{ $val }}" {{ old('payment_type', $category->payment_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('payment_type')" />

                            <div x-cloak x-show="type === 'custom_months'" class="mt-3">
                                <x-input-label for="interval_months" :value="__('Interval (months)')" />
                                <x-text-input id="interval_months" name="interval_months" type="number" min="1" max="24" class="mt-1 block w-full" :value="old('interval_months', $category->interval_months)" />
                                <x-input-error class="mt-2" :messages="$errors->get('interval_months')" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="sm:col-span-1">
                                <x-input-label for="default_amount" :value="__('Amount per student')" />
                                <x-text-input id="default_amount" name="default_amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full" :value="old('default_amount', $category->default_amount)" />
                                <x-input-error class="mt-2" :messages="$errors->get('default_amount')" />
                            </div>
                            <div class="sm:col-span-1">
                                <x-input-label for="first_due_date" :value="__('First due date')" />
                                <x-text-input id="first_due_date" name="first_due_date" type="date" class="mt-1 block w-full" :value="old('first_due_date', optional($category->first_due_date)->toDateString())" />
                                <x-input-error class="mt-2" :messages="$errors->get('first_due_date')" />
                            </div>
                            <div class="sm:col-span-1">
                                <x-input-label for="reminder_days_before" :value="__('Reminder days before')" />
                                <x-text-input id="reminder_days_before" name="reminder_days_before" type="number" min="0" max="60" class="mt-1 block w-full" :value="old('reminder_days_before', $category->reminder_days_before ?? 5)" />
                                <x-input-error class="mt-2" :messages="$errors->get('reminder_days_before')" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="description" :value="__('Description')" />
                            <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" :value="old('description', $category->description)" />
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>

                        <div>
                            <label class="inline-flex items-center gap-2">
                                <input type="hidden" name="active" value="0" />
                                <input type="checkbox" name="active" value="1" class="rounded border-gray-300" {{ old('active', $category->active ? '1' : '0') === '1' ? 'checked' : '' }}>
                                <span class="text-sm text-gray-800">Active</span>
                            </label>
                        </div>

                        <div>
                            <div class="flex items-center gap-2">
                                <input type="hidden" name="applies_to_all" value="0" />
                                <input id="applies_to_all" type="checkbox" name="applies_to_all" value="1" class="rounded border-gray-300" {{ old('applies_to_all', $category->applies_to_all ? '1' : '0') === '1' ? 'checked' : '' }}>
                                <label for="applies_to_all" class="text-sm text-gray-800">Applies to all classes</label>
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('applies_to_all')" />

                            <div class="mt-3">
                                <div class="text-xs font-semibold text-gray-600">If not all, select applicable classes:</div>
                                <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                    @php
                                        $selected = array_map('strval', old('class_room_ids', $selectedClassRoomIds ?? []));
                                        $amountOld = old('class_room_amounts', []);
                                        $pivotAmounts = $category->classRooms()->pluck('class_room_revenue_category.amount', 'class_rooms.id')->toArray();
                                    @endphp
                                    @foreach ($classRooms as $cr)
                                        @php
                                            $id = (string) $cr->id;
                                            $val = $amountOld[$id] ?? $amountOld[(int) $cr->id] ?? ($pivotAmounts[$cr->id] ?? null);
                                        @endphp
                                        <div class="flex items-center gap-2">
                                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 flex-1">
                                                <input type="checkbox" name="class_room_ids[]" value="{{ $cr->id }}" class="rounded border-gray-300" {{ in_array((string) $cr->id, $selected) ? 'checked' : '' }}>
                                                <span>{{ $cr->level !== null ? ('Level '.$cr->level.' - ') : '' }}{{ $cr->name }}</span>
                                            </label>
                                            <input type="number" min="0.01" step="0.01" name="class_room_amounts[{{ $cr->id }}]" value="{{ $val }}"
                                                class="w-28 rounded-md border-gray-300 text-sm" placeholder="Amt">
                                        </div>
                                    @endforeach
                                </div>
                                <x-input-error class="mt-2" :messages="$errors->get('class_room_ids')" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Save</x-primary-button>
                            <a href="{{ route('revenue.categories.index') }}" class="text-sm text-gray-600 hover:underline">Back</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
