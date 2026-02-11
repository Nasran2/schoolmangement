<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Salary Components & Deductions</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                @if(session('status'))
                    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('settings.salary-components.update') }}" id="salary-settings-form">
                    @csrf
                    @method('PUT')

                    <div class="grid md:grid-cols-2 gap-8">
                        <!-- Component Types -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">Salary Component Types</h3>
                                    <p class="text-sm text-gray-500">These populate the dropdowns in teacher salary breakdowns.</p>
                                </div>
                                <button type="button" onclick="addRow('component')" class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">+ Add</button>
                            </div>

                            <div id="component-list" class="space-y-2">
                                @foreach($componentTypes as $type)
                                    <div class="flex items-center gap-2">
                                        <input type="text" name="component_types[]" value="{{ $type }}" class="flex-1 border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500" required>
                                        <button type="button" onclick="removeRow(this)" class="px-2 py-2 text-red-600 hover:text-red-700">✕</button>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Deduction Types -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-800">Deduction Types</h3>
                                    <p class="text-sm text-gray-500">Shown when adding deductions to teacher salary payments.</p>
                                </div>
                                <button type="button" onclick="addRow('deduction')" class="px-3 py-1.5 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">+ Add</button>
                            </div>

                            <div id="deduction-list" class="space-y-2">
                                @foreach($deductionTypes as $type)
                                    <div class="flex items-center gap-2">
                                        <input type="text" name="deduction_types[]" value="{{ $type }}" class="flex-1 border-gray-300 rounded-md focus:border-red-500 focus:ring-red-500" required>
                                        <button type="button" onclick="removeRow(this)" class="px-2 py-2 text-red-600 hover:text-red-700">✕</button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- EPF / ETF Settings -->
                    <div class="mt-8 border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">EPF / ETF Percentages</h3>
                        <p class="text-sm text-gray-500 mb-4">These percentages are applied only on the teacher's basic salary.</p>

                        <div class="grid md:grid-cols-3 gap-6">
                            <div>
                                <label for="employee_epf_percent" class="text-sm font-medium text-gray-700">Employee EPF (%)</label>
                                <input id="employee_epf_percent" name="employee_epf_percent" type="number" step="0.01" min="0" max="100"
                                       value="{{ old('employee_epf_percent', $employeeEpfPercent ?? 0) }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label for="employer_epf_percent" class="text-sm font-medium text-gray-700">Company EPF (%)</label>
                                <input id="employer_epf_percent" name="employer_epf_percent" type="number" step="0.01" min="0" max="100"
                                       value="{{ old('employer_epf_percent', $employerEpfPercent ?? 12) }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label for="employer_etf_percent" class="text-sm font-medium text-gray-700">Company ETF (%)</label>
                                <input id="employer_etf_percent" name="employer_etf_percent" type="number" step="0.01" min="0" max="100"
                                       value="{{ old('employer_etf_percent', $employerEtfPercent ?? 3) }}"
                                       class="mt-1 block w-full border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200">Cancel</a>
                        <x-primary-button>Save Settings</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function addRow(type) {
            const listId = type === 'component' ? 'component-list' : 'deduction-list';
            const list = document.getElementById(listId);
            const inputName = type === 'component' ? 'component_types[]' : 'deduction_types[]';
            if (!list) return;

            const wrapper = document.createElement('div');
            wrapper.className = 'flex items-center gap-2';
            wrapper.innerHTML = `
                <input type="text" name="${inputName}" class="flex-1 border-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500" required>
                <button type="button" onclick="removeRow(this)" class="px-2 py-2 text-red-600 hover:text-red-700">✕</button>
            `;
            list.appendChild(wrapper);
        }

        function removeRow(button) {
            const parent = button.closest('div');
            const list = button.closest('#component-list, #deduction-list');
            if (!list || list.children.length <= 1) {
                alert('At least one entry is required.');
                return;
            }
            parent.remove();
        }
    </script>
</x-app-layout>
