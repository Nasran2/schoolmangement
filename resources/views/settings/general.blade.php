<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('School General Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('settings.general.update') }}" class="space-y-6" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="space-y-6">
                                <div>
                                    <x-input-label for="school_name" :value="__('School Name')" />
                                    <x-text-input id="school_name" name="school_name" type="text" class="mt-1 block w-full" :value="old('school_name', $school_name)" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('school_name')" />
                                </div>

                                <div>
                                    <x-input-label for="academic_year" :value="__('Academic Year')" />
                                    <x-text-input id="academic_year" name="academic_year" type="text" class="mt-1 block w-full" :value="old('academic_year', $academic_year)" placeholder="2025-2026" />
                                    <x-input-error class="mt-2" :messages="$errors->get('academic_year')" />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="school_logo" :value="__('School Logo')" />
                                <div class="mt-2 flex items-center gap-x-3">
                                    @if($school_logo)
                                        <img src="{{ url('/storage/'.$school_logo) }}" alt="School Logo" class="h-12 w-12 rounded-md object-cover ring-1 ring-gray-200">
                                        <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                            <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300">
                                            <span>Remove</span>
                                        </label>
                                    @else
                                        <x-application-logo class="h-12 w-12 text-gray-300" />
                                    @endif
                                    <input type="file" id="school_logo" name="school_logo" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" accept="image/*">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">SVG, PNG, JPG or GIF (MAX. 2MB).</p>
                                <x-input-error class="mt-2" :messages="$errors->get('school_logo')" />
                            </div>

                            <div>
                                <x-input-label for="login_background" :value="__('Login Background Image')" />
                                <div class="mt-2 flex items-center gap-x-3">
                                    @if(!empty($login_background))
                                        <img src="{{ url('/storage/'.$login_background) }}" alt="Login Background" class="h-12 w-20 rounded-md object-cover ring-1 ring-gray-200">
                                        <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                            <input type="checkbox" name="remove_login_background" value="1" class="rounded border-gray-300">
                                            <span>Remove</span>
                                        </label>
                                    @else
                                        <span class="text-xs text-gray-500">No background selected</span>
                                    @endif
                                    <input type="file" id="login_background" name="login_background" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" accept="image/*">
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Large landscape image recommended (JPG/PNG, MAX. 6MB). Displayed on the login page for all users.</p>
                                <x-input-error class="mt-2" :messages="$errors->get('login_background')" />
                            </div>
                        </div>

                        <div class="border-t pt-6">
                            <h3 class="text-base font-semibold text-gray-900">School Contact Information</h3>
                            <p class="mt-1 text-sm text-gray-600">This information will appear on receipts and reports.</p>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <x-input-label for="school_address" :value="__('Address')" />
                                    <textarea id="school_address" name="school_address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('school_address', $school_address ?? '') }}</textarea>
                                    <x-input-error class="mt-2" :messages="$errors->get('school_address')" />
                                </div>
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <x-input-label for="school_phone" :value="__('Phone')" />
                                        <x-text-input id="school_phone" name="school_phone" type="text" class="mt-1 block w-full" :value="old('school_phone', $school_phone ?? '')" />
                                        <x-input-error class="mt-2" :messages="$errors->get('school_phone')" />
                                    </div>
                                    <div>
                                        <x-input-label for="school_email" :value="__('Email')" />
                                        <x-text-input id="school_email" name="school_email" type="email" class="mt-1 block w-full" :value="old('school_email', $school_email ?? '')" />
                                        <x-input-error class="mt-2" :messages="$errors->get('school_email')" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-t pt-6">
                            <h3 class="text-base font-semibold text-gray-900">Receipt Settings</h3>
                            <p class="mt-1 text-sm text-gray-600">Configure receipt printing behavior.</p>
                            <div class="mt-4">
                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="auto_print_receipt" value="0" />
                                    <input type="checkbox" name="auto_print_receipt" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('auto_print_receipt', $auto_print_receipt ?? false) == '1' ? 'checked' : '' }} />
                                    <span class="text-sm text-gray-800">Automatically print receipt after saving payment</span>
                                </label>
                                <p class="mt-1 text-xs text-gray-500">When enabled, the receipt will automatically open in print dialog after saving a payment.</p>
                                <x-input-error class="mt-2" :messages="$errors->get('auto_print_receipt')" />
                            </div>

                            <div class="mt-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <x-input-label for="receipt_paper_width" :value="__('Receipt Width')" />
                                        <x-text-input id="receipt_paper_width" name="receipt_paper_width" type="text" class="mt-1 block w-full" value="{{ old('receipt_paper_width', $receipt_paper_width ?? '5.5in') }}" placeholder="e.g. 5.5in" />
                                        <x-input-error class="mt-2" :messages="$errors->get('receipt_paper_width')" />
                                    </div>
                                    <div>
                                        <x-input-label for="receipt_paper_height" :value="__('Receipt Height')" />
                                        <x-text-input id="receipt_paper_height" name="receipt_paper_height" type="text" class="mt-1 block w-full" value="{{ old('receipt_paper_height', $receipt_paper_height ?? '11in') }}" placeholder="e.g. 11in" />
                                        <x-input-error class="mt-2" :messages="$errors->get('receipt_paper_height')" />
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Specify the exact width and height that should be used for printing receipts (include units such as `in` or `mm`).</p>
                            </div>
                        </div>

                        <div class="border-t pt-6">
                            <h3 class="text-base font-semibold text-gray-900">Teacher Salary Payments</h3>
                            <p class="mt-1 text-sm text-gray-600">Configure behavior after recording teacher salary payments.</p>
                            <div class="mt-4">
                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="auto_email_teacher_payslip" value="0" />
                                    <input type="checkbox" name="auto_email_teacher_payslip" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('auto_email_teacher_payslip', $auto_email_teacher_payslip ?? false) == '1' ? 'checked' : '' }} />
                                    <span class="text-sm text-gray-800">Automatically email payslip to teacher after saving salary payment</span>
                                </label>
                                <p class="mt-1 text-xs text-gray-500">Requires Email (SMTP) settings and a teacher email address.</p>
                                <x-input-error class="mt-2" :messages="$errors->get('auto_email_teacher_payslip')" />
                            </div>
                        </div>

                        <div class="border-t pt-6">
                            <h3 class="text-base font-semibold text-gray-900">Bill Numbering (Revenue)</h3>
                            <p class="mt-1 text-sm text-gray-600">Configure automatic bill number generation for revenues.</p>

                            <div class="mt-4">
                                <label class="inline-flex items-center gap-2">
                                    <input type="hidden" name="revenue_bill_autogenerate" value="0" />
                                    <input type="checkbox" name="revenue_bill_autogenerate" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('revenue_bill_autogenerate', $revenue_bill_autogenerate) == '1' ? 'checked' : '' }} />
                                    <span class="text-sm text-gray-800">Auto-generate bill number</span>
                                </label>
                                <x-input-error class="mt-2" :messages="$errors->get('revenue_bill_autogenerate')" />
                            </div>

                            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <x-input-label for="revenue_bill_prefix" :value="__('Prefix')" />
                                    <x-text-input id="revenue_bill_prefix" name="revenue_bill_prefix" type="text" class="mt-1 block w-full" :value="old('revenue_bill_prefix', $revenue_bill_prefix)" />
                                    <x-input-error class="mt-2" :messages="$errors->get('revenue_bill_prefix')" />
                                </div>

                                <div>
                                    <x-input-label for="revenue_bill_start_number" :value="__('Start Number')" />
                                    <x-text-input id="revenue_bill_start_number" name="revenue_bill_start_number" type="number" class="mt-1 block w-full" :value="old('revenue_bill_start_number', $revenue_bill_start_number)" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('revenue_bill_start_number')" />
                                </div>

                                <div>
                                    <x-input-label for="revenue_bill_next_number" :value="__('Next Number')" />
                                    <x-text-input id="revenue_bill_next_number" name="revenue_bill_next_number" type="number" class="mt-1 block w-full" :value="old('revenue_bill_next_number', $revenue_bill_next_number)" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('revenue_bill_next_number')" />
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
