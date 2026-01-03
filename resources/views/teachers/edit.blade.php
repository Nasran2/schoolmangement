<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Edit Teacher</h2>
                <p class="text-gray-600 text-sm mt-1">Update teacher profile and assignments</p>
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
                    <h3 class="text-lg font-semibold text-gray-800">Teacher Information</h3>
                    <p class="text-sm text-gray-600 mt-1">Update the teacher's details below</p>
                </div>

                <!-- Form Body -->
                <form method="POST" action="{{ route('teachers.update', $teacher) }}" class="p-8">
                    @csrf
                    @method('PUT')

                    <!-- Name Field -->
                    <div class="mb-8">
                        <x-input-label for="name" :value="__('Full Name')" class="font-semibold mb-2" />
                        <x-text-input 
                            id="name" 
                            name="name" 
                            type="text" 
                            class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm" 
                            :value="old('name', $teacher->name)" 
                            placeholder="e.g., John Smith"
                            required 
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('name')" />
                    </div>

                    <!-- Email Field -->
                    <div class="mb-8">
                        <x-input-label for="email" :value="__('Email Address')" class="font-semibold mb-2" />
                        <x-text-input 
                            id="email" 
                            name="email" 
                            type="email" 
                            class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm" 
                            :value="old('email', $teacher->email ?? '')" 
                            placeholder="john@example.com"
                        />
                        <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        <p class="text-gray-500 text-xs mt-1">Optional: For email communications</p>
                    </div>

                    <!-- Two Column Layout -->
                    <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 mb-8">
                        <!-- Phone Number -->
                        <div>
                            <x-input-label for="phone" :value="__('Phone Number')" class="font-semibold mb-2" />
                            <x-text-input 
                                id="phone" 
                                name="phone" 
                                type="tel" 
                                class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm" 
                                :value="old('phone', $teacher->phone ?? '')" 
                                placeholder="e.g., +1 (555) 123-4567"
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
                        </div>

                        <!-- Joining Date -->
                        <div>
                            <x-input-label for="joining_date" :value="__('Joining Date')" class="font-semibold mb-2" />
                            <x-text-input 
                                id="joining_date" 
                                name="joining_date" 
                                type="date" 
                                class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm" 
                                :value="old('joining_date', optional($teacher->joining_date)->format('Y-m-d'))" 
                            />
                            <x-input-error class="mt-2" :messages="$errors->get('joining_date')" />
                        </div>

                        <!-- Payment Start Date -->
                        <div class="sm:col-span-2">
                            <x-input-label for="payment_start_date" :value="__('Payment Start Date')" class="font-semibold mb-2" />
                            <x-text-input 
                                id="payment_start_date" 
                                name="payment_start_date" 
                                type="date" 
                                class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm" 
                                :value="old('payment_start_date', optional($teacher->payment_start_date)->format('Y-m-d'))" 
                            />
                            <p class="text-gray-500 text-xs mt-1">Date when monthly salary payments should start</p>
                            <x-input-error class="mt-2" :messages="$errors->get('payment_start_date')" />
                        </div>
                    </div>

                    <!-- Address Field -->
                    <div class="mb-8">
                        <x-input-label for="address" :value="__('Address')" class="font-semibold mb-2" />
                        <textarea 
                            id="address" 
                            name="address" 
                            class="mt-1 block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm"
                            rows="2"
                            placeholder="123 Main Street, City, State, ZIP"
                        >{{ old('address', $teacher->address ?? '') }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('address')" />
                    </div>

                    <!-- Assigned Classes Section -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                        <div class="mb-4">
                            <x-input-label for="assigned_classes_hidden" :value="__('Assign Classes (Optional)')" class="font-semibold mb-3 block" />
                            <p class="text-sm text-gray-600 mb-4">Select one or more classes to assign to this teacher</p>
                            
                            @if($classrooms->isEmpty())
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                    <p class="text-sm text-yellow-800">No active classes available. Please create classes first.</p>
                                </div>
                            @else
                                @php
                                    $currentAssignedClasses = $teacher->assigned_classes ? explode(',', $teacher->assigned_classes) : [];
                                @endphp
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                    @foreach($classrooms as $classroom)
                                        @php
                                            $isChecked = in_array($classroom->id, $currentAssignedClasses) || in_array((string)$classroom->id, $currentAssignedClasses);
                                        @endphp
                                        <label class="flex items-center p-3 border-2 border-blue-200 rounded-lg hover:border-blue-400 hover:bg-blue-100 cursor-pointer transition {{ $isChecked ? 'bg-blue-100 border-blue-400' : '' }}">
                                            <input 
                                                type="checkbox" 
                                                name="assigned_classes" 
                                                value="{{ $classroom->id }}"
                                                {{ $isChecked ? 'checked' : '' }}
                                                class="h-4 w-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                                            >
                                            <span class="ml-2 text-sm font-medium text-gray-800">
                                                {{ $classroom->name }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <input type="hidden" id="assigned_classes_hidden" name="assigned_classes_hidden" value="{{ old('assigned_classes_hidden', $teacher->assigned_classes) }}">
                    </div>

                    <!-- Salary Section -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <x-input-label for="salary_amount" :value="__('Salary Components')" class="font-semibold text-lg" />
                                <p class="text-sm text-gray-600 mt-1">Break down the monthly salary into components</p>
                            </div>
                            <button 
                                type="button" 
                                onclick="addSalaryComponent()" 
                                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition shadow-sm"
                            >
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path>
                                </svg>
                                Add Component
                            </button>
                        </div>

                        <div id="salary-components-container" class="space-y-3 mb-4">
                            @if($teacher->salary_components && count($teacher->salary_components) > 0)
                                @foreach($teacher->salary_components as $index => $component)
                                <div class="salary-component flex gap-3 items-start bg-white p-4 rounded-lg border border-gray-200">
                                    <div class="flex-1">
                                        @php
                                            $defaultTypes = ['Basic Salary','House Allowance','Transport Allowance','Medical Allowance','Incentive','Bonus','Special Allowance','Other'];
                                            $isCustom = !in_array($component['type'], $defaultTypes);
                                        @endphp
                                        <select 
                                            name="salary_components[{{ $index }}][type]" 
                                            class="block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm text-sm"
                                            onchange="handleCustomType(this)"
                                            required
                                        >
                                            @foreach($componentTypes as $type)
                                                <option value="{{ $type }}" {{ $component['type'] === $type ? 'selected' : '' }}>{{ $type }}</option>
                                            @endforeach
                                            <option value="__custom__">Custom...</option>
                                            @if($isCustom)
                                                <option value="{{ $component['type'] }}" selected>{{ $component['type'] }}</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="w-48">
                                        <div class="relative">
                                            <span class="absolute left-3 top-2.5 text-gray-500 text-sm">Rs</span>
                                            <input 
                                                type="number" 
                                                name="salary_components[{{ $index }}][amount]" 
                                                step="0.01" 
                                                min="0"
                                                value="{{ $component['amount'] }}"
                                                placeholder="0.00" 
                                                class="block w-full pl-10 border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm text-sm"
                                                oninput="calculateTotalSalary()"
                                                required
                                            />
                                        </div>
                                    </div>
                                    <button 
                                        type="button" 
                                        onclick="removeSalaryComponent(this)" 
                                        class="p-2 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg transition"
                                        title="Remove"
                                    >
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                                @endforeach
                            @else
                                <!-- Default component if none exist -->
                                <div class="salary-component flex gap-3 items-start bg-white p-4 rounded-lg border border-gray-200">
                                    <div class="flex-1">
                                        <select 
                                            name="salary_components[0][type]" 
                                            class="block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm text-sm"
                                            onchange="handleCustomType(this)"
                                            required
                                        >
                                            @foreach($componentTypes as $type)
                                                <option value="{{ $type }}">{{ $type }}</option>
                                            @endforeach
                                            <option value="__custom__">Custom...</option>
                                        </select>
                                    </div>
                                    <div class="w-48">
                                        <div class="relative">
                                            <span class="absolute left-3 top-2.5 text-gray-500 text-sm">Rs</span>
                                            <input 
                                                type="number" 
                                                name="salary_components[0][amount]" 
                                                step="0.01" 
                                                min="0"
                                                value="{{ $teacher->salary_amount }}"
                                                placeholder="0.00" 
                                                class="block w-full pl-10 border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm text-sm"
                                                oninput="calculateTotalSalary()"
                                                required
                                            />
                                        </div>
                                    </div>
                                    <button 
                                        type="button" 
                                        onclick="removeSalaryComponent(this)" 
                                        class="p-2 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg transition"
                                        title="Remove"
                                    >
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endif
                        </div>

                        <!-- Total Salary Display -->
                        <div class="bg-white border-2 border-green-500 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-gray-700 uppercase">Total Monthly Salary:</span>
                                <span class="text-2xl font-bold text-green-600" id="total-salary-display">Rs {{ number_format($teacher->salary_amount, 2) }}</span>
                            </div>
                        </div>

                        <!-- Hidden field for total salary -->
                        <input type="hidden" id="salary_amount" name="salary_amount" value="{{ $teacher->salary_amount }}">
                        <x-input-error class="mt-2" :messages="$errors->get('salary_amount')" />
                        <p class="text-gray-500 text-xs mt-2">The total will be calculated automatically</p>
                    </div>

                    <!-- Statutory Deductions (EPF/ETF) -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                        <h4 class="text-lg font-semibold text-gray-800 mb-3">Statutory Deductions</h4>
                        <p class="text-sm text-gray-600 mb-4">Enable or disable EPF/ETF for this teacher. These apply only to the Basic Salary and use percentages from Settings.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="flex items-center">
                                <input type="hidden" name="epf_enabled" value="0" />
                                <label class="flex items-center cursor-pointer">
                                    <span class="mr-3 font-medium text-gray-800">EPF Enabled</span>
                                    <span class="relative inline-block w-12 h-6 bg-gray-300 rounded-full">
                                        <input 
                                            type="checkbox" 
                                            id="epf_enabled" 
                                            name="epf_enabled" 
                                            value="1" 
                                            {{ old('epf_enabled', $teacher->epf_enabled ? '1' : '0') === '1' ? 'checked' : '' }}
                                            class="sr-only peer"
                                        >
                                        <span class="absolute inset-0 rounded-full bg-gradient-to-r from-blue-400 to-blue-600 opacity-0 peer-checked:opacity-100 transition-opacity duration-300"></span>
                                        <span class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full peer-checked:left-7 transition-all duration-300"></span>
                                    </span>
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input type="hidden" name="etf_enabled" value="0" />
                                <label class="flex items-center cursor-pointer">
                                    <span class="mr-3 font-medium text-gray-800">ETF Enabled</span>
                                    <span class="relative inline-block w-12 h-6 bg-gray-300 rounded-full">
                                        <input 
                                            type="checkbox" 
                                            id="etf_enabled" 
                                            name="etf_enabled" 
                                            value="1" 
                                            {{ old('etf_enabled', $teacher->etf_enabled ? '1' : '0') === '1' ? 'checked' : '' }}
                                            class="sr-only peer"
                                        >
                                        <span class="absolute inset-0 rounded-full bg-gradient-to-r from-blue-400 to-blue-600 opacity-0 peer-checked:opacity-100 transition-opacity duration-300"></span>
                                        <span class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full peer-checked:left-7 transition-all duration-300"></span>
                                    </span>
                                </label>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">EPF/ETF amounts will be calculated automatically when recording salary payments.</p>
                    </div>

                    <!-- Custom Component Modal -->
                    <div id="custom-type-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40">
                        <div class="bg-white rounded-lg shadow-xl w-96 p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Add Custom Component</h3>
                            <p class="text-sm text-gray-600 mb-3">Enter a label for this salary component.</p>
                            <input id="custom-type-input" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:border-green-500 focus:ring-green-500" placeholder="e.g., Research Allowance" />
                            <div class="mt-4 flex justify-end gap-3">
                                <button type="button" onclick="closeCustomTypeModal()" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300">Cancel</button>
                                <button type="button" onclick="saveCustomType()" class="px-4 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700">Add</button>
                            </div>
                        </div>
                    </div>

                    <!-- Status Section -->
                    <div class="bg-purple-50 border border-purple-200 rounded-lg p-6 mb-8">
                        <label class="flex items-start sm:items-center gap-3 cursor-pointer select-none w-full">
                            <input type="hidden" name="active" value="0" />
                            <span class="relative inline-block w-12 h-6 bg-gray-300 rounded-full shrink-0 mt-0.5 sm:mt-0">
                                <input 
                                    type="checkbox" 
                                    id="active" 
                                    name="active" 
                                    value="1" 
                                    {{ old('active', $teacher->active ? '1' : '0') === '1' ? 'checked' : '' }}
                                    class="sr-only peer"
                                    onchange="updateToggleLabel()"
                                >
                                <div class="absolute inset-0 rounded-full bg-gradient-to-r from-green-400 to-green-600 opacity-0 peer-checked:opacity-100 transition-opacity duration-300"></div>
                                <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full peer-checked:left-7 transition-all duration-300"></div>
                            </span>
                            <span class="font-medium text-gray-800">
                                Teacher Status: <span id="status-label" class="{{ old('active', $teacher->active ? '1' : '0') === '1' ? 'text-green-600' : 'text-red-600' }} font-bold">{{ old('active', $teacher->active ? '1' : '0') === '1' ? 'Active' : 'Inactive' }}</span>
                            </span>
                        </label>
                        <p class="text-gray-500 text-xs mt-2 ml-0 sm:ml-12">Toggle to mark teacher as active or inactive</p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center gap-4 pt-6 border-t border-gray-200">
                        <x-primary-button class="bg-blue-600 hover:bg-blue-700">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Update Teacher
                        </x-primary-button>
                        <a href="{{ route('teachers.show', $teacher) }}" class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium transition">
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

        // Handle assigned classes checkboxes
        document.querySelectorAll('input[name="assigned_classes"]').forEach(checkbox => {
            checkbox.addEventListener('change', updateAssignedClasses);
        });

        function updateAssignedClasses() {
            const checkboxes = document.querySelectorAll('input[name="assigned_classes"]:checked');
            const values = Array.from(checkboxes).map(cb => cb.value).join(',');
            const hiddenInput = document.getElementById('assigned_classes_hidden');
            if (hiddenInput) {
                hiddenInput.value = values;
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateAssignedClasses();
            updateToggleLabel();
            calculateTotalSalary();
        });

        // Salary Components Management
        let componentIndex = {{ $teacher->salary_components ? count($teacher->salary_components) : 1 }};
        const componentOptions = @json($componentTypes);
        const componentOptionsHtml = componentOptions.map(type => `<option value="${type}">${type}</option>`).join('') + '<option value="__custom__">Custom...</option>';

        function addSalaryComponent() {
            const container = document.getElementById('salary-components-container');
            const componentHtml = `
                <div class="salary-component flex gap-3 items-start bg-white p-4 rounded-lg border border-gray-200">
                    <div class="flex-1">
                        <select 
                            name="salary_components[${componentIndex}][type]" 
                            class="block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm text-sm"
                            onchange="handleCustomType(this)"
                            required
                        >${componentOptionsHtml}</select>
                    </div>
                    <div class="w-48">
                        <div class="relative">
                            <span class="absolute left-3 top-2.5 text-gray-500 text-sm">Rs</span>
                            <input 
                                type="number" 
                                name="salary_components[${componentIndex}][amount]" 
                                step="0.01" 
                                min="0"
                                placeholder="0.00" 
                                class="block w-full pl-10 border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-lg shadow-sm text-sm"
                                oninput="calculateTotalSalary()"
                                required
                            />
                        </div>
                    </div>
                    <button 
                        type="button" 
                        onclick="removeSalaryComponent(this)" 
                        class="p-2 bg-red-100 hover:bg-red-200 text-red-600 rounded-lg transition"
                        title="Remove"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', componentHtml);
            componentIndex++;
        }

        function removeSalaryComponent(button) {
            const components = document.querySelectorAll('.salary-component');
            if (components.length > 1) {
                button.closest('.salary-component').remove();
                calculateTotalSalary();
            } else {
                alert('At least one salary component is required');
            }
        }

        let pendingCustomSelect = null;

        function handleCustomType(selectEl) {
            if (selectEl.value === '__custom__') {
                pendingCustomSelect = selectEl;
                const modal = document.getElementById('custom-type-modal');
                const input = document.getElementById('custom-type-input');
                if (input) input.value = '';
                if (modal) modal.classList.remove('hidden');
            }
        }

        function closeCustomTypeModal() {
            const modal = document.getElementById('custom-type-modal');
            if (modal) modal.classList.add('hidden');
            if (pendingCustomSelect) {
                pendingCustomSelect.value = 'Basic Salary';
                pendingCustomSelect = null;
            }
        }

        function saveCustomType() {
            const modal = document.getElementById('custom-type-modal');
            const input = document.getElementById('custom-type-input');
            const value = (input?.value || '').trim();
            if (!pendingCustomSelect) {
                if (modal) modal.classList.add('hidden');
                return;
            }
            if (!value) {
                pendingCustomSelect.value = 'Basic Salary';
                if (modal) modal.classList.add('hidden');
                pendingCustomSelect = null;
                return;
            }

            const option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            pendingCustomSelect.appendChild(option);
            pendingCustomSelect.value = value;

            if (modal) modal.classList.add('hidden');
            pendingCustomSelect = null;
        }

        function calculateTotalSalary() {
            const amounts = document.querySelectorAll('input[name^="salary_components"][name$="[amount]"]');
            let total = 0;
            
            amounts.forEach(input => {
                const value = parseFloat(input.value) || 0;
                total += value;
            });
            
            document.getElementById('total-salary-display').textContent = 'Rs ' + total.toFixed(2);
            document.getElementById('salary_amount').value = total.toFixed(2);
        }
    </script>
</x-app-layout>
