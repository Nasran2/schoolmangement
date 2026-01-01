<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Edit Student</h2>
                <p class="text-gray-600 text-sm mt-1">Update student profile and enrollment details</p>
            </div>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-800 flex items-center">
                    <svg class="w-5 h-5 mr-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-lg border border-gray-100">
                <!-- Form Header -->
                <div class="border-b border-gray-200 px-6 py-6 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <h3 class="text-lg font-semibold text-gray-800">Student Information</h3>
                    <p class="text-sm text-gray-600 mt-1">Update the student's details below</p>
                </div>

                <!-- Form Body -->
                <form method="POST" action="{{ route('students.update', $student) }}" class="p-8">
                    @csrf
                    @method('PUT')

                    <!-- Admission Number -->
                    <div class="mb-8">
                        <x-input-label for="admission_number" :value="__('Admission Number')" class="font-semibold mb-2" />
                        <x-text-input 
                            id="admission_number" 
                            name="admission_number" 
                            type="text" 
                            class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm" 
                            :value="old('admission_number', $student->admission_number)" 
                            placeholder="e.g., STU-2025-001"
                            required 
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('admission_number')" />
                        <p class="text-gray-500 text-xs mt-1">Unique identifier for this student</p>
                    </div>

                    <!-- Hidden Full Name (auto from first + other names) -->
                    <input type="hidden" id="full_name_hidden" name="name" value="{{ old('name', $student->name) }}" />

                    <!-- Combined Admission & Personal UI -->
                    @include('students._admission_fields', ['student' => $student])

                    <!-- Academic Information Section -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8">
                        <h4 class="text-sm font-semibold text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                            </svg>
                            Academic Information
                        </h4>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <x-input-label for="class_room_id" :value="__('Class')" class="font-semibold mb-2" />
                                <select 
                                    id="class_room_id" 
                                    name="class_room_id" 
                                    class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm" 
                                    required
                                >
                                    <option value="" disabled {{ old('class_room_id', $student->class_room_id) ? '' : 'selected' }}>Select class</option>
                                    @foreach ($classRooms as $cr)
                                        <option value="{{ $cr->id }}" data-monthly-fee="{{ $cr->monthly_fee }}" @selected((string) old('class_room_id', $student->class_room_id) === (string) $cr->id)>
                                            {{ $cr->level !== null ? ('Level '.$cr->level.' - ') : '' }}{{ $cr->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('class_room_id')" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="fee_start_date" :value="__('Payment Start Date')" class="font-semibold mb-2 block mt-6" />
                            <x-text-input 
                                id="fee_start_date" 
                                name="fee_start_date" 
                                type="date" 
                                class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm" 
                                :value="old('fee_start_date', optional($student->fee_start_date)->format('Y-m-d'))" 
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('fee_start_date')" />
                            <p class="mt-1 text-xs text-gray-500">First month when monthly fee is due. This is used to compute outstanding dues.</p>
                        </div>
                    </div>

                    <!-- Fee Information Section -->
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-6 mb-8">
                        <h4 class="text-sm font-semibold text-gray-800 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8.16 2.75a.75.75 0 00-1.32 0l-3.5 7A.75.75 0 004.5 11h11a.75.75 0 00.66-1.25l-3.5-7zM9 13.25a.75.75 0 00-.75.75v3.5a.75.75 0 001.5 0v-3.5a.75.75 0 00-.75-.75z"></path>
                            </svg>
                            Fee Details
                        </h4>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <x-input-label for="monthly_fee" :value="__('Monthly Fee')" class="font-semibold mb-2" />
                                <div class="relative">
                                    <span class="absolute left-3 top-3 text-gray-500 font-semibold">Rs</span>
                                    <x-text-input 
                                        id="monthly_fee" 
                                        name="monthly_fee" 
                                        type="number" 
                                        step="0.01" 
                                        min="0"
                                        class="mt-1 block w-full pl-8 border-gray-300 focus:border-amber-500 focus:ring-amber-500 rounded-lg shadow-sm" 
                                        :value="old('monthly_fee', $student->monthly_fee)" 
                                        placeholder="0.00"
                                    />
                                </div>
                                <x-input-error class="mt-2" :messages="$errors->get('monthly_fee')" />
                                <p class="text-gray-500 text-xs mt-1">Auto-filled from selected class, also updates Due Amount</p>
                            </div>

                            <div>
                                <x-input-label for="due_amount" :value="__('Outstanding Due')" class="font-semibold mb-2" />
                                <div class="mt-1 flex items-center gap-2 px-4 py-3 border-2 border-amber-300 rounded-lg bg-amber-100 text-amber-900 font-semibold">
                                    <span class="text-lg">Rs</span>
                                    <span id="due_amount_display" class="text-xl">{{ old('due_amount', $student->due_amount) }}</span>
                                    <span class="ml-auto text-sm text-amber-700" id="due_months_display"></span>
                                </div>
                                <p class="text-gray-500 text-xs mt-1">Auto-calculated from monthly fee and start date</p>
                            </div>
                        </div>
                    </div>

                    <!-- Admission Details Section -->
                    @include('students._admission_fields', ['student' => $student])

                    <!-- Status Section -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8">
                        <input type="hidden" name="active" value="0" />
                        <div class="flex items-center">
                            <label for="active" class="relative inline-block w-12 h-6 bg-gray-300 rounded-full cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    id="active" 
                                    name="active" 
                                    value="1" 
                                    {{ old('active', $student->active ? '1' : '0') === '1' ? 'checked' : '' }}
                                    class="sr-only peer"
                                    onchange="updateToggleLabel()"
                                >
                                <div class="absolute inset-0 rounded-full bg-gradient-to-r from-green-400 to-green-600 opacity-0 peer-checked:opacity-100 transition-opacity duration-300"></div>
                                <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full peer-checked:left-7 transition-all duration-300"></div>
                            </label>
                            <label for="active" class="ml-3 font-medium text-gray-800 cursor-pointer">
                                Student Status: <span id="status-label" class="{{ old('active', $student->active ? '1' : '0') === '1' ? 'text-green-600' : 'text-red-600' }} font-bold">{{ old('active', $student->active ? '1' : '0') === '1' ? 'Active' : 'Inactive' }}</span>
                            </label>
                        </div>
                        <p class="text-gray-500 text-xs mt-2 ml-12">Toggle to enroll or disable this student</p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center gap-4 pt-6 border-t border-gray-200">
                        <x-primary-button class="bg-blue-600 hover:bg-blue-700">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Update Student
                        </x-primary-button>
                        <a href="{{ route('students.show', $student) }}" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium transition">
                            Back
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Update toggle label
        function updateToggleLabel() {
            const checkbox = document.getElementById('active');
            const label = document.getElementById('status-label');
            if (checkbox.checked) {
                label.textContent = 'Active';
                label.classList.remove('text-red-600');
                label.classList.add('text-green-600');
            } else {
                label.textContent = 'Inactive';
                label.classList.remove('text-green-600');
                label.classList.add('text-red-600');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const firstNameInput = document.getElementById('first_name');
            const otherNamesInput = document.getElementById('other_names');
            const fullNameHidden = document.getElementById('full_name_hidden');

            const updateFullName = () => {
                const first = (firstNameInput?.value || '').trim();
                const other = (otherNamesInput?.value || '').trim();
                const joined = (first + ' ' + other).trim().replace(/\s+/g, ' ');
                if (fullNameHidden) fullNameHidden.value = joined;
            };
            if (firstNameInput) firstNameInput.addEventListener('input', updateFullName);
            if (otherNamesInput) otherNamesInput.addEventListener('input', updateFullName);
            updateFullName();
            const classSelect = document.getElementById('class_room_id');
            const monthlyFeeInput = document.getElementById('monthly_fee');
            const dueAmountDisplay = document.getElementById('due_amount_display');
            const feeStartInput = document.getElementById('fee_start_date');
            const dueMonthsDisplay = document.getElementById('due_months_display');

            if (!classSelect || !monthlyFeeInput) return;

            const monthsBetweenInclusive = (fromStr) => {
                if (!fromStr) return 1;
                const from = new Date(fromStr);
                if (Number.isNaN(from.getTime())) return 1;
                const now = new Date();
                let months = (now.getFullYear() - from.getFullYear()) * 12 + (now.getMonth() - from.getMonth());
                if (months < 0) months = 0;
                months += 1;
                return months;
            };

            const updateDueAmount = () => {
                const fee = Number(monthlyFeeInput.value || 0);
                const months = monthsBetweenInclusive(feeStartInput?.value);
                dueAmountDisplay.textContent = (fee * months).toFixed(2);
                if (dueMonthsDisplay) dueMonthsDisplay.textContent = months + ' month' + (months === 1 ? '' : 's');
            };

            const applyMonthlyFeeFromSelectedClass = (force = false) => {
                const opt = classSelect.options[classSelect.selectedIndex];
                if (!opt) return;
                const rawFee = opt.getAttribute('data-monthly-fee');
                if (rawFee === null || rawFee === undefined) return;

                const fee = Number(rawFee);
                if (Number.isNaN(fee)) return;

                const current = (monthlyFeeInput.value ?? '').trim();
                if (!force && current !== '' && Number(current) !== 0) return;

                monthlyFeeInput.value = fee.toFixed(2);
                updateDueAmount();
            };

            classSelect.addEventListener('change', () => applyMonthlyFeeFromSelectedClass(true));
            monthlyFeeInput.addEventListener('input', updateDueAmount);
            if (feeStartInput) feeStartInput.addEventListener('change', updateDueAmount);
            applyMonthlyFeeFromSelectedClass(false);
            updateDueAmount();
            updateToggleLabel();
        });
    </script>
</x-app-layout>
