@php
    $isEdit = isset($item);
    $pageTitle = $isEdit ? 'Edit Revenue' : 'Add Revenue';
    $pageSubtitle = $isEdit ? 'Update payment transaction details' : 'Complete your payment transaction';
    $formAction = $isEdit ? route('revenue.items.update', $item) : route('revenue.items.store');
    $selectedCategoryId = old('revenue_category_id', $preselectedCategoryId ?? ($isEdit ? $item->revenue_category_id : null));
    $defaultAmount = old('amount', $isEdit ? (string) $item->amount : '');
    $defaultPaidAt = old('paid_at', $isEdit ? optional($item->paid_at)->format('d-m-Y') : date('d-m-Y'));
    $paymentMeta = $isEdit && is_array($item->payment_meta ?? null) ? $item->payment_meta : [];
    $defaultPaymentMethod = old('payment_method', $isEdit ? ($item->payment_method ?: 'cash') : 'cash');
    $defaultBankName = old('bank_name', (string) ($paymentMeta['bank'] ?? ''));
    $defaultBankRefNo = old('bank_ref_no', (string) ($paymentMeta['ref_no'] ?? ''));
    $defaultChequeDate = old('cheque_date', $isEdit ? optional($item->cheque_date)->format('Y-m-d') : '');
    $defaultChequeNumber = old('cheque_number', (string) ($paymentMeta['cheque_number'] ?? ''));
    $defaultChequeBank = old('cheque_bank', $defaultPaymentMethod === 'cheque' ? (string) ($paymentMeta['bank'] ?? '') : '');
    $defaultChequeStudentName = old('cheque_student_name', (string) ($paymentMeta['student_name'] ?? ''));
    $defaultBillNo = old('bill_no', $isEdit ? ($item->bill_no ?? '') : ($nextBillNumberPreview ?? ''));
    $billNoDisplay = $isEdit ? ($item->bill_no ?: 'Auto-generated') : ($nextBillNumberPreview ?: 'Auto-generated');
    $defaultNotes = old('notes', $isEdit ? ($item->notes ?? '') : '');
    $submitLabel = $isEdit ? 'Update Payment' : 'Save Payment';
    $initialSelectedStudentId = old('student_id', $selectedStudentId ?? ($isEdit ? $item->student_id : ''));
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-900 leading-tight">{{ $pageTitle }}</h2>
                <p class="mt-1 text-sm text-gray-600">{{ $pageSubtitle }}</p>
            </div>
            <a href="{{ route('revenue.items.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                Back to Revenue
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-b from-slate-50 to-white min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8" x-data="revenueForm()">
                {{-- Left Side: Form --}}
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-lg sm:rounded-2xl border border-gray-200">
                        <div class="px-6 py-8 sm:px-8">
                            <h3 class="text-2xl font-bold text-gray-900 mb-8">Payment Details</h3>

                            <form id="revenue-form" method="POST" action="{{ $formAction }}"
                                class="space-y-7">
                                @csrf
                                @if ($isEdit)
                                    @method('PUT')
                                @endif

                                {{-- Category with Add Button --}}
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-3">Payment
                                        Category</label>
                                    <div class="flex gap-2">
                                        <div class="flex-1">
                                            <select id="revenue_category_id" name="revenue_category_id"
                                                class="block w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all"
                                                x-model="formData.category_id" x-on:change="updateSummary(); updateAllocationPreview()">
                                                <option value="">Select Category</option>
                                                @foreach ($categories as $cat)
                                                    <option value="{{ $cat->id }}" data-name="{{ $cat->name }}"
                                                        data-type="{{ $cat->payment_type }}" @selected((string) $selectedCategoryId === (string) $cat->id)>
                                                        {{ $cat->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="button" x-on:click="showCategoryModal = true"
                                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-lg hover:from-indigo-600 hover:to-indigo-700 shadow-sm transition-all font-medium"
                                            title="Add new category">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            Add
                                        </button>
                                    </div>

                                    <div x-show="categoryType" x-cloak class="mt-3">
                                        <span
                                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold"
                                            :class="categoryType === 'monthly' ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700'">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                                                viewBox="0 0 24 24" class="w-3 h-3">
                                                <path
                                                    d="M9.195 18.44c.059.468.076.96.076 1.456 0 1.193-.668 2.326-1.634 2.653C5.503 23.008 2 20.7 2 17.72c0-1.229.756-2.291 1.823-2.775 1.017 2.484 3.582 4.479 6.372 4.495z" />
                                                <path
                                                    d="M9 6a4 4 0 100-8 4 4 0 000 8zm15 0a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            <span
                                                x-text="categoryType.charAt(0).toUpperCase() + categoryType.slice(1) + ' Fee'"></span>
                                        </span>
                                    </div>
                                    @error('revenue_category_id')
                                        <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Student Picker --}}
                                <div x-data="studentPicker()" x-init="init()"
                                    data-student-id="{{ $initialSelectedStudentId }}"
                                    data-exclude-revenue-id="{{ $isEdit ? (int) $item->id : '' }}">
                                    <label class="block text-sm font-semibold text-gray-800 mb-3">Student <span
                                            class="font-normal text-gray-500">(optional)</span></label>

                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        <div class="sm:col-span-2">
                                            <div class="relative">
                                                <input type="text"
                                                    class="block w-full px-4 py-2.5 rounded-lg border-gray-300 pr-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all"
                                                    placeholder="Search by name, admission no, phone" x-model="q"
                                                    x-on:input.debounce.300ms="search()"
                                                    x-on:keydown="handleKeyDown($event)">
                                                <button type="button"
                                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                                                    x-on:click="openDefault()"
                                                    :disabled="isLoading">
                                                    <svg x-show="!isLoading" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2">
                                                        <circle cx="11" cy="11" r="8" />
                                                        <path d="M21 21l-4.3-4.3" />
                                                    </svg>
                                                    <svg x-show="isLoading" x-cloak class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M21 12a9 9 0 11-6.219-8.56" />
                                                    </svg>
                                                </button>
                                                <div x-show="open" x-cloak
                                                    class="absolute z-20 mt-2 w-full rounded-lg border border-gray-200 bg-white shadow-xl max-h-64 overflow-auto">
                                                    <template x-if="results.length === 0">
                                                        <div class="px-4 py-4 text-sm text-gray-600 text-center">No
                                                            matches found.</div>
                                                    </template>
                                                    <template x-for="(item, idx) in results" :key="item.id">
                                                        <button type="button"
                                                            class="flex w-full items-center justify-between px-4 py-3.5 text-left transition-colors border-b border-gray-100 last:border-0"
                                                            :class="highlightedIndex === idx ? 'bg-indigo-50' : 'hover:bg-gray-50'"
                                                            x-on:click="select(item); $dispatch('student-selected', item)">
                                                            <div class="min-w-0 flex-1">
                                                                <div class="text-sm font-semibold text-gray-900 truncate"
                                                                    x-text="item.name"></div>
                                                                <div class="text-xs text-gray-500 truncate mt-0.5">
                                                                    <span x-text="item.class"></span> ·
                                                                    <span x-text="item.admission_number"></span>
                                                                </div>
                                                            </div>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <select
                                                class="block w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all"
                                                x-model="classRoomId" x-on:change="search()">
                                                <option value="">All Classes</option>
                                                @foreach ($classRooms as $cr)
                                                    <option value="{{ $cr->id }}">{{ $cr->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <input type="hidden" name="student_id" :value="selected?.id || ''">
                                    @error('student_id')
                                        <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                    @enderror

                                    <div x-show="selected" x-cloak
                                        class="mt-4 rounded-xl border-2 border-indigo-500 bg-gradient-to-r from-indigo-50 to-blue-50 p-5">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-bold text-indigo-900" x-text="selected?.name">
                                                </p>
                                                <p class="text-xs text-indigo-700 mt-2">
                                                    <span class="inline-block px-2 py-1 bg-indigo-100 rounded">Class:
                                                        <span x-text="selected?.class"></span></span>
                                                </p>
                                                <p class="text-xs text-indigo-700 mt-1">
                                                    <span class="inline-block px-2 py-1 bg-indigo-100 rounded">ID: <span
                                                            x-text="selected?.admission_number"></span></span>
                                                </p>
                                            </div>
                                            <button type="button"
                                                class="p-2 hover:bg-indigo-200 rounded-lg transition-colors flex-shrink-0"
                                                x-on:click="clearSelection()">
                                                <svg class="w-5 h-5 text-indigo-700" fill="none" stroke="currentColor"
                                                    stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Due Information --}}
                                <div x-show="categoryType === 'monthly' && studentName && selectedCategoryIsMonthlyFee" x-cloak
                                    class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-xl">
                                    <div class="flex items-start gap-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-amber-600 mt-0.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                        </svg>
                                        <div class="flex-1">
                                            <h4 class="text-sm font-bold text-amber-900">Payment Status</h4>
                                            <div class="mt-1 text-sm text-amber-800">
                                                <p>Total Due: <span class="font-bold">Rs <span x-text="Number(studentDueAmount).toLocaleString('en', {minimumFractionDigits: 2})"></span></span></p>
                                                <div x-show="Number(studentHoldAmount) > 0" class="mt-2 rounded-lg border border-amber-300 bg-amber-100/70 px-3 py-2 text-xs text-amber-900">
                                                    <p class="font-semibold">On Hold: Rs <span x-text="formatMoney(studentHoldAmount)"></span></p>
                                                    <p class="mt-1">This amount is on hold until cheque is passed.</p>
                                                    <p x-show="Array.isArray(studentHoldChequeNumbers) && studentHoldChequeNumbers.length > 0" class="mt-1">
                                                        Cheque No:
                                                        <span class="font-semibold" x-text="studentHoldChequeNumbers.join(', ')"></span>
                                                    </p>
                                                </div>
                                                
                                                {{-- Due Months (auto allocated oldest-first) --}}
                                                <div x-show="studentDueMonths.length > 0" class="mt-3">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700 mb-2">Due months (auto allocated oldest-first):</p>
                                                    <div class="flex flex-wrap gap-2">
                                                        <template x-for="(month, index) in studentDueMonths" :key="index">
                                                            <div class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-medium border border-amber-200 bg-white text-amber-800">
                                                                <span x-text="typeof month === 'object' ? month.label : month"></span>
                                                                <template x-if="typeof month === 'object' && month.amount !== undefined">
                                                                    <span class="ml-1 text-[11px] text-amber-700">(Rs <span x-text="formatMoney(month.amount)"></span>)</span>
                                                                </template>
                                                            </div>
                                                        </template>
                                                    </div>
                                                    <p class="text-[10px] text-amber-600 mt-1.5 italic">* Enter an amount; the system will pay the oldest due months first (full/partial), then advance months if any.</p>
                                                </div>

                                                <div x-show="studentDueMonths.length === 0 && studentDueAmount <= 0" class="mt-2 text-green-700 font-medium flex items-center gap-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                                    </svg>
                                                    No dues pending
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Year-wise fee override --}}
                                <div x-show="categoryType === 'monthly' && studentName && selectedCategoryIsMonthlyFee" x-cloak
                                    class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50/50 p-4">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">Edit Year Amounts</p>
                                            <p class="text-xs text-gray-500">Set one monthly payment amount for each year for this student.</p>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" x-on:click="addYearlyFeeRow()"
                                                class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">
                                                Add Year
                                            </button>
                                            <button type="button" x-on:click="setAmountFromYearlyFeeRows()"
                                                class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                                Use Payable
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-4 space-y-3">
                                        <template x-for="(row, index) in yearlyFeeRows" :key="row.uid">
                                            <div class="grid grid-cols-1 gap-3 rounded-lg border border-indigo-100 bg-white p-3 sm:grid-cols-9 sm:items-end">
                                                <div class="sm:col-span-3">
                                                    <label class="block text-[11px] font-semibold uppercase text-gray-500">Year</label>
                                                    <input type="number" min="2000" step="1" x-model="row.year" x-on:input="yearlyFeeRowsChanged()"
                                                        class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                </div>
                                                <div class="sm:col-span-5">
                                                    <label class="block text-[11px] font-semibold uppercase text-gray-500">Monthly Amount</label>
                                                    <div class="relative mt-1">
                                                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-semibold text-gray-500">Rs</span>
                                                        <input type="number" min="0.01" step="0.01" x-model="row.fee_amount" x-on:input="yearlyFeeRowsChanged()"
                                                            class="block w-full rounded-lg border-gray-300 pl-9 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    </div>
                                                </div>
                                                <div class="sm:col-span-1">
                                                    <button type="button" x-on:click="removeYearlyFeeRow(index)"
                                                        class="flex h-10 w-10 items-center justify-center rounded-lg text-gray-500 hover:bg-rose-50 hover:text-rose-600"
                                                        title="Remove row">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 6L6 18M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>

                                        <p x-show="yearlyFeeRows.length === 0" class="rounded-lg border border-dashed border-indigo-200 bg-white px-3 py-4 text-center text-xs text-gray-500">
                                            No custom year amounts added.
                                        </p>
                                    </div>

                                    <div class="mt-3 flex justify-between border-t border-indigo-100 pt-3 text-sm">
                                        <span class="font-medium text-gray-600">Payable total</span>
                                        <span class="font-bold text-indigo-700">Rs <span x-text="formatMoney(yearlyFeeRowsPayableTotal())"></span></span>
                                    </div>

                                    <input type="hidden" name="monthly_fee_overrides" :value="JSON.stringify(yearlyFeeRowsPayload())">
                                </div>

                                {{-- Advance payment toggle --}}
                                <div x-show="categoryType === 'monthly' && selectedCategoryIsMonthlyFee" x-transition.opacity x-cloak class="mt-6">
                                    <div class="flex items-center justify-between p-4 rounded-xl border border-gray-200 bg-gray-50/50 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-indigo-100 text-indigo-600 rounded-lg">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">Advance Payment</p>
                                                <p class="text-xs text-gray-500" x-show="studentName">Pay for upcoming months</p>
                                                <p class="text-xs text-amber-600" x-show="!studentName">Select a student to enable</p>
                                            </div>
                                        </div>
                                        
                                        <button type="button"
                                            @click="if(!studentName) return; advanceMode = !advanceMode; if(!advanceMode){ selectedAdvanceMonths=[]; selectedAdvanceLabels=[]; updateAllocationPreview(); }"
                                            :disabled="!studentName"
                                            :class="[advanceMode ? 'bg-indigo-600' : 'bg-gray-200', !studentName ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer']"
                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                            <span :class="advanceMode ? 'translate-x-6' : 'translate-x-1'"
                                                class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm"></span>
                                        </button>
                                    </div>
                                </div>

                                {{-- Advance Months selector --}}
                                <div x-show="categoryType === 'monthly' && studentName && selectedCategoryIsMonthlyFee && advanceMode" 
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-2"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-cloak 
                                    class="mt-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                    <div class="flex items-center justify-between mb-4">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">Select Months</p>
                                            <p class="text-xs text-gray-500">Choose which future months to pay</p>
                                        </div>
                                        <span class="text-xs font-medium px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-full" x-show="selectedAdvanceLabels.length > 0" x-text="selectedAdvanceLabels.length + ' selected'"></span>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="m in advanceOptions" :key="m.key">
                                            <button type="button"
                                                @click="toggleAdvanceMonth(m.key, m.label)"
                                                :class="isAdvanceSelected(m.key)
                                                    ? 'bg-indigo-600 text-white border-indigo-600 shadow-md ring-2 ring-indigo-200 ring-offset-1'
                                                    : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-300 hover:text-indigo-600 hover:bg-indigo-50'"
                                                class="flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-medium transition-all duration-200">
                                                <span x-text="m.label"></span>
                                                <svg x-show="isAdvanceSelected(m.key)" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
                                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </template>
                                    </div>

                                    <div class="mt-4 pt-3 border-t border-gray-100 flex justify-between items-center" x-show="selectedAdvanceLabels.length > 0" x-transition>
                                        <div class="flex items-center gap-2">
                                            <div class="p-1.5 bg-indigo-50 text-indigo-600 rounded-md">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.732 6.232a2.5 2.5 0 013.536 0 .75.75 0 101.06-1.06A4 4 0 006.5 8v.165c0 .364.034.728.1 1.085h-.35a.75.75 0 000 1.5h.737a5.25 5.25 0 01-.367 3.072l-.055.123a.75.75 0 001.37.61 3.75 3.75 0 00.256-1.508l.001-.797h2.106a.75.75 0 000-1.5h-2.1v-.165a2.5 2.5 0 012.536-2.536zM10 8a2.5 2.5 0 00-2.5 2.5V12h1.8a.75.75 0 000-1.5h-1.8v-1.5A1 1 0 018.5 8h3a1 1 0 011 1v2.5h-1.8a.75.75 0 000 1.5h1.8v.5a1 1 0 01-1 1h-1.3a.75.75 0 000 1.5h1.3A2.5 2.5 0 0014 13.5V10.5A2.5 2.5 0 0011.5 8h-1.5z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <span class="text-xs font-medium text-gray-600">Advance Amount Required</span>
                                        </div>
                                        <span class="text-sm font-bold text-indigo-700">Rs <span x-text="advanceRequiredAmount().toFixed(2)"></span></span>
                                    </div>

                                    <input type="hidden" name="advance_months" :value="JSON.stringify(selectedAdvanceMonths.map(k => { const [y,mo]=k.split('-'); return {year:+y,month:+mo}; }))">
                                </div>

                                {{-- Amount and Date --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800 mb-3" x-text="isAdmissionFee() ? 'Base Amount' : 'Amount'">Amount</label>
                                        <div class="relative">
                                            <span
                                                class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-600 font-semibold">Rs</span>
                                            <input type="number" id="amount_input" name="base_amount" step="0.01" min="0.01"
                                                class="block w-full pl-12 pr-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all"
                                                placeholder="0.00" value="{{ $defaultAmount }}"
                                                x-model="formData.amount"
                                                x-on:input="updateAllocationPreview()" required>
                                        </div>
                                        <input type="hidden" name="amount" :value="finalPayableAmount()">
                                        
                                        {{-- Discount Section for Admission Fee --}}
                                        <div x-show="isAdmissionFee()" x-cloak class="mt-4 p-4 border border-indigo-100 bg-indigo-50/50 rounded-xl transition-all">
                                            <label class="block text-sm font-semibold text-gray-800 mb-3">Discount (Optional)</label>
                                            <div class="flex gap-3">
                                                <div class="w-2/5">
                                                    <select name="discount_type" x-model="discountType" x-on:change="updateAllocationPreview()"
                                                        class="block w-full px-3 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-all text-sm">
                                                        <option value="percentage">Percentage (%)</option>
                                                        <option value="fixed">Fixed Amount</option>
                                                    </select>
                                                </div>
                                                <div class="w-3/5 relative">
                                                    <input type="number" name="discount_value" step="0.01" min="0"
                                                        class="block w-full px-3 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 transition-all text-sm"
                                                        placeholder="Discount value" x-model="discountValue" x-on:input="updateAllocationPreview()">
                                                </div>
                                            </div>
                                            <div class="mt-3 flex justify-between items-center text-sm" x-show="discountValue > 0">
                                                <span class="font-medium text-gray-600">Discount Amount</span>
                                                <span class="font-bold text-red-500">- Rs <span x-text="formatMoney(calculatedDiscountAmount())"></span></span>
                                            </div>
                                            <div class="mt-2 pt-2 border-t border-indigo-200 flex justify-between items-center text-sm" x-show="discountValue > 0">
                                                <span class="font-semibold text-gray-800">Final Payable</span>
                                                <span class="font-bold text-indigo-700">Rs <span x-text="formatMoney(finalPayableAmount())"></span></span>
                                            </div>
                                        </div>
                                        <p class="mt-2 text-xs text-gray-500" x-show="categoryType === 'monthly' && selectedCategoryIsMonthlyFee">If you pay extra, it will automatically go to next months.</p>
                                        <div class="mt-2 text-xs" x-show="categoryType === 'monthly' && selectedCategoryIsMonthlyFee && selectedStudentId && Number(formData.amount||0) > 0" x-cloak>
                                            <p class="text-amber-700" x-show="allocationFlowText()" x-text="allocationFlowText()"></p>
                                        </div>
                                        @error('amount')
                                            <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800 mb-3">Payment
                                            Date</label>
                                        <input type="text" id="paid_at_input" name="paid_at" placeholder="DD-MM-YYYY"
                                            class="block w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all"
                                            value="{{ $defaultPaidAt }}"
                                            x-model="formData.date"
                                            x-on:input="formData.date = $event.target.value" required>
                                        @error('paid_at')
                                            <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Payment Method --}}
                                <div class="mt-2">
                                    <label class="block text-sm font-semibold text-gray-800 mb-3">Payment Method</label>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <label class="flex items-center gap-2 rounded-xl border px-4 py-3 cursor-pointer transition"
                                            :class="formData.payment_method==='cash' ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200' : 'border-gray-200 bg-white hover:bg-gray-50'">
                                            <input type="radio" name="payment_method" value="cash" class="text-indigo-600 focus:ring-indigo-500"
                                                x-model="formData.payment_method">
                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-gray-900">Cash</div>
                                                <div class="text-xs text-gray-500">No extra details</div>
                                            </div>
                                        </label>

                                        <label class="flex items-center gap-2 rounded-xl border px-4 py-3 cursor-pointer transition"
                                            :class="formData.payment_method==='bank_transfer' ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200' : 'border-gray-200 bg-white hover:bg-gray-50'">
                                            <input type="radio" name="payment_method" value="bank_transfer" class="text-indigo-600 focus:ring-indigo-500"
                                                x-model="formData.payment_method">
                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-gray-900">Bank Transfer</div>
                                                <div class="text-xs text-gray-500">Ref No + Bank (optional)</div>
                                            </div>
                                        </label>

                                        <label class="flex items-center gap-2 rounded-xl border px-4 py-3 cursor-pointer transition"
                                            :class="formData.payment_method==='cheque' ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-200' : 'border-gray-200 bg-white hover:bg-gray-50'">
                                            <input type="radio" name="payment_method" value="cheque" class="text-indigo-600 focus:ring-indigo-500"
                                                x-model="formData.payment_method">
                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-gray-900">Cheque</div>
                                                <div class="text-xs text-gray-500">Pending confirmation</div>
                                            </div>
                                        </label>
                                    </div>
                                    @error('payment_method')
                                        <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                    @enderror

                                    {{-- Bank Transfer Fields --}}
                                    <div x-show="formData.payment_method === 'bank_transfer'" x-cloak class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-800 mb-3">Bank <span class="font-normal text-gray-500">(optional)</span></label>
                                            <input type="text" name="bank_name"
                                                class="block w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all"
                                                placeholder="Bank name" value="{{ $defaultBankName }}">
                                            @error('bank_name')
                                                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-800 mb-3">Reference No <span class="font-normal text-gray-500">(optional)</span></label>
                                            <input type="text" name="bank_ref_no"
                                                class="block w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all"
                                                placeholder="Transaction reference" value="{{ $defaultBankRefNo }}">
                                            @error('bank_ref_no')
                                                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>

                                    {{-- Cheque Fields (pending confirmation) --}}
                                    <div x-show="formData.payment_method === 'cheque'" x-cloak class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-800 mb-3">Cheque Date</label>
                                            <input type="date" name="cheque_date"
                                                class="block w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all"
                                                value="{{ $defaultChequeDate }}">
                                            @error('cheque_date')
                                                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-800 mb-3">Cheque Number</label>
                                            <input type="text" name="cheque_number"
                                                class="block w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all"
                                                placeholder="Cheque number" value="{{ $defaultChequeNumber }}">
                                            @error('cheque_number')
                                                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-800 mb-3">Bank</label>
                                            <input type="text" name="cheque_bank"
                                                class="block w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all"
                                                placeholder="Bank name" value="{{ $defaultChequeBank }}">
                                            @error('cheque_bank')
                                                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-800 mb-3">Student Name <span class="font-normal text-gray-500">(optional)</span></label>
                                            <input type="text" name="cheque_student_name"
                                                class="block w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all"
                                                placeholder="Student name (as on cheque)" value="{{ $defaultChequeStudentName }}">
                                            @error('cheque_student_name')
                                                <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div class="sm:col-span-2">
                                            <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                                                Cheque payments will be saved as <span class="font-semibold">Pending confirmation</span> until approved.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {{-- Allocation summary --}}
                                <div x-show="categoryType === 'monthly' && selectedStudentId && formData.amount" x-cloak class="mt-6 p-4 bg-indigo-50 border border-indigo-200 rounded-xl">
                                    <div class="mt-1">
                                        <h5 class="text-xs font-semibold text-indigo-800 mb-2">Allocation summary</h5>
                                        <div x-show="isAllocationLoading" class="flex items-center gap-2 text-xs text-indigo-700 mb-2" x-cloak>
                                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 12a9 9 0 11-6.219-8.56" />
                                            </svg>
                                            <span>Loading preview…</span>
                                        </div>
                                        <template x-if="allocation.allocations.length === 0">
                                            <p class="text-xs text-indigo-700">Enter amount and select advance months (optional) to preview.</p>
                                        </template>
                                        <template x-for="a in allocation.allocations" :key="a.year+'-'+a.month">
                                            <div class="flex justify-between text-xs py-1">
                                                <div>
                                                    <span x-text="monthName(a.month)+' '+a.year"></span>
                                                    <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded-full border" :class="a.type==='due' ? 'border-amber-300 text-amber-800 bg-amber-100' : 'border-emerald-300 text-emerald-800 bg-emerald-100'">
                                                        <span x-text="a.type==='due' ? 'Due' : 'Advance'"></span>
                                                    </span>
                                                    <span x-show="a.is_partial" class="ml-2 text-rose-700">(Partial)</span>
                                                </div>
                                                <div class="text-right">
                                                    <span>Rs </span><span x-text="Number(a.applied_amount).toFixed(2)"></span>
                                                    <span x-show="a.is_partial" class="ml-2 text-xs text-gray-600">Remaining: Rs <span x-text="Number(a.remaining_for_month).toFixed(2)"></span></span>
                                                </div>
                                            </div>
                                        </template>
                                        <div class="mt-2 pt-2 border-t text-xs">
                                            <div class="flex justify-between"><span>Total applied</span><span>Rs <span x-text="Number(allocation.summary.total_applied||0).toFixed(2)"></span></span></div>
                                            <div class="flex justify-between"><span>Unallocated balance</span><span>Rs <span x-text="Number(allocation.summary.unallocated_balance||0).toFixed(2)"></span></span></div>
                                            <template x-if="(allocation.summary.errors||[]).length > 0">
                                                <div class="mt-2 text-rose-700" x-text="allocation.summary.errors.join(' ')"></div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                {{-- Bill Number --}}
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-3">Bill Number <span
                                            class="font-normal text-gray-500">(optional)</span></label>
                                    @if($autogenerate)
                                        <input type="text"
                                            class="block w-full px-4 py-2.5 rounded-lg border-gray-300 bg-gray-100 text-gray-600 shadow-sm"
                                            value="{{ $billNoDisplay }}" disabled readonly>
                                        <p class="mt-2 text-xs text-gray-500">
                                            {{ $isEdit ? 'This bill number is managed automatically from Settings.' : 'Next bill number preview. Final value is assigned when you save.' }}
                                            To enter/edit it manually, disable
                                            <span class="font-semibold">Auto-generate bill number</span> in Settings.
                                        </p>
                                    @else
                                        <input type="text" name="bill_no"
                                            class="block w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all"
                                            placeholder="Enter bill number" value="{{ $defaultBillNo }}"
                                            x-model="formData.bill_no">
                                        <p class="mt-2 text-xs text-gray-500">
                                            Suggested next bill: <span class="font-semibold">{{ $nextBillNumberPreview }}</span> (you can change it).
                                        </p>
                                    @endif
                                    @error('bill_no')
                                        <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Notes --}}
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-3">Notes <span
                                            class="font-normal text-gray-500">(optional)</span></label>
                                    <textarea name="notes" rows="3"
                                        class="block w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all"
                                        placeholder="Add any additional notes...">{{ $defaultNotes }}</textarea>
                                    @error('notes')
                                        <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Submit Buttons --}}
                                <div class="flex items-center gap-4 pt-8 border-t border-gray-200">
                                    <button type="submit"
                                        class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 border border-transparent rounded-xl font-semibold text-white shadow-lg hover:from-indigo-700 hover:to-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all hover:shadow-xl">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                            stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>{{ $submitLabel }}</span>
                                    </button>
                                    <a href="{{ route('revenue.items.index') }}"
                                        class="px-6 py-3 bg-gray-100 border border-gray-200 rounded-xl font-semibold text-gray-700 shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 transition-colors">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                {{-- Right Side: Billing Summary --}}
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-lg sm:rounded-2xl border border-gray-200 sticky top-6">
                        <div class="px-6 py-8 sm:px-8">
                            <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                    stroke="currentColor" class="w-6 h-6 text-indigo-600">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .984.575 1.83 1.407 2.228.62.56 1.405.949 2.493.949.882 0 1.642-.949 1.738-1.813.032-.268.06-.56.06-.856 0-.331-.035-.624-.1-.864m0 0C9.806 2.852 9.426 2.25 8.25 2.25 7.065 2.25 6 3.494 6 5.008c0 1.518 1.062 2.813 2.258 2.813.859 0 1.579-.597 1.681-1.438m0 0M16.5 12.75h.008v.008h-.008v-.008z" />
                                </svg>
                                Billing Summary
                            </h3>

                            <div class="space-y-4 mb-8">
                                <div class="flex justify-between items-start gap-3 pb-4 border-b border-gray-100">
                                    <span class="text-sm font-medium text-gray-600">Category:</span>
                                    <span class="text-sm font-semibold text-gray-900 text-right"
                                        x-text="categoryName || '—'"></span>
                                </div>
                                <div class="flex justify-between items-start gap-3 pb-4 border-b border-gray-100">
                                    <span class="text-sm font-medium text-gray-600">Student:</span>
                                    <span class="text-sm font-semibold text-gray-900 text-right"
                                        x-text="studentName || '—'"></span>
                                </div>
                                <div class="flex justify-between items-start gap-3 pb-4 border-b border-gray-100">
                                    <span class="text-sm font-medium text-gray-600">Date:</span>
                                    <span class="text-sm font-semibold text-gray-900 text-right"
                                        x-text="formData.date || '—'"></span>
                                </div>
                            </div>

                            <div
                                class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-5 mb-6 border border-indigo-200">
                                <div class="text-center">
                                    <p class="text-xs uppercase tracking-wide font-semibold text-indigo-700 mb-1">Total
                                        Amount</p>
                                    <div class="flex items-baseline justify-center gap-1">
                                        <span class="text-sm font-medium text-indigo-700">Rs</span>
                                        <span class="text-3xl font-bold text-indigo-600"
                                            x-text="Number(finalPayableAmount() || 0).toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                                <div class="flex gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                    </svg>
                                    <div class="text-sm text-blue-900">
                                        <p class="font-semibold mb-1">This payment will cover:</p>
                                        <p class="text-xs text-blue-800" x-text="coverageSummaryText()"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Add Category Modal --}}
                <div x-show="showCategoryModal" x-cloak
                    class="fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center"
                    x-on:click.self="showCategoryModal = false">
                    <div class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-2xl p-6 sm:p-8 animate-in fade-in slide-in-from-bottom-4 duration-300"
                        @click.stop>
                        <div class="flex items-center justify-between mb-6">
                            <h4 class="text-xl font-bold text-gray-900">Add New Category</h4>
                            <button type="button" x-on:click="showCategoryModal = false"
                                class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                    class="w-5 h-5 text-gray-500">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form method="POST" action="{{ route('revenue.categories.store') }}"
                            class="space-y-5" id="add-category-form" x-data="{ type: 'one_time', appliesToAll: true }">
                            @csrf
                            <div id="add-category-error" class="hidden rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"></div>

                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-800 mb-2">Category
                                    Name</label>
                                <input type="text" name="name"
                                    placeholder="e.g., Tuition Fee, Registration"
                                    class="block w-full px-4 py-2.5 border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all"
                                    required>
                            </div>

                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-800 mb-2">Payment
                                    Type</label>
                                <select name="payment_type" x-model="type"
                                    class="block w-full px-4 py-2.5 border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all">
                                    <option value="monthly">Monthly</option>
                                    <option value="2_months">Every 2 Months</option>
                                    <option value="3_months">Every 3 Months</option>
                                    <option value="6_months">Every 6 Months</option>
                                    <option value="yearly">Yearly</option>
                                    <option value="custom_months">Custom (Every N Months)</option>
                                    <option value="one_time">One-time</option>
                                </select>
                            </div>

                            <div x-cloak x-show="type === 'custom_months'">
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Interval (months)</label>
                                <input type="number" name="interval_months" min="1" max="24"
                                    placeholder="e.g., 4"
                                    class="block w-full px-4 py-2.5 border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all">
                            </div>

                            <div x-cloak x-show="type !== 'one_time'" class="space-y-3">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">Amount per student</label>
                                    <input type="number" name="default_amount" min="0.01" step="0.01"
                                        placeholder="e.g., 1500"
                                        class="block w-full px-4 py-2.5 border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all">
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800 mb-2">First due date</label>
                                        <input type="date" name="first_due_date" value="{{ now()->toDateString() }}"
                                            class="block w-full px-4 py-2.5 border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800 mb-2">Reminder (days before)</label>
                                        <input type="number" name="reminder_days_before" min="0" max="60" value="5"
                                            class="block w-full px-4 py-2.5 border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Description (optional)</label>
                                <input type="text" name="description"
                                    class="block w-full px-4 py-2.5 border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all">
                            </div>

                            <div class="rounded-lg border border-gray-200 p-3">
                                <div class="flex items-center gap-2">
                                    <input type="hidden" name="applies_to_all" value="0" />
                                    <input id="modal_applies_to_all" type="checkbox" name="applies_to_all" value="1" class="rounded border-gray-300" x-model="appliesToAll" checked>
                                    <label for="modal_applies_to_all" class="text-sm text-gray-800">Applies to all classes</label>
                                </div>

                                <div x-cloak x-show="!appliesToAll" class="mt-3">
                                    <div class="text-xs font-semibold text-gray-600">Select applicable classes:</div>
                                    <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2 max-h-40 overflow-auto pr-1">
                                        @foreach ($classRooms as $cr)
                                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                <input type="checkbox" name="class_room_ids[]" value="{{ $cr->id }}" class="rounded border-gray-300">
                                                <span>{{ $cr->level !== null ? ('Level '.$cr->level.' - ') : '' }}{{ $cr->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-3 pt-4">
                                <button type="button" x-on:click="showCategoryModal = false"
                                    class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-lg hover:from-indigo-600 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed font-semibold transition-all">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                                        class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    <span>Add Category</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('revenueForm', () => ({
                    formData: {
                        category_id: '{{ $selectedCategoryId }}',
                        amount: '{{ $defaultAmount }}',
                        date: '{{ $defaultPaidAt }}',
                        bill_no: '{{ $autogenerate ? '' : $defaultBillNo }}',
                        payment_method: '{{ $defaultPaymentMethod }}',
                    },
                    editingRevenueId: '{{ $isEdit ? (int) $item->id : '' }}',
                    categories: @json($categories),
                    categoryName: '',
                    categoryType: '',
                    selectedCategoryIsMonthlyFee: false,
                    studentName: '',
                    studentDueAmount: 0,
                    studentMonthlyFee: 0,
                    studentDueMonths: [],
                    studentHoldAmount: 0,
                    studentHoldChequeNumbers: [],
                    showCategoryModal: false,
                    selectedStudentId: '{{ $initialSelectedStudentId }}',
                    monthlyCatId: '{{ $monthlyCatId ?? '' }}',
                    advanceEnabled: false,
                    advanceMode: false,
                    advanceOptions: [],
                    futureMonths: [],
                    selectedAdvanceKeys: new Set(),
                    selectedAdvanceMonths: [],
                    selectedAdvanceLabels: [],
                    yearlyFeeRows: [],
                    yearlyFeeRowSeq: 0,
                    yearlyFeePreviewTimer: null,
                    discountType: 'percentage',
                    discountValue: '',
                    allocation: { allocations: [], summary: { total_applied: 0, unallocated_balance: 0, paid_due_months: [], advance_months: [], errors: [] } },
                    isAllocationLoading: false,

                    isAdmissionFee() {
                        return this.categoryName && this.categoryName.toLowerCase().includes('admission fee');
                    },
                    calculatedDiscountAmount() {
                        let amt = parseFloat(this.formData.amount) || 0;
                        if (!this.isAdmissionFee() || !this.discountValue || parseFloat(this.discountValue) <= 0) return 0;
                        
                        let dVal = parseFloat(this.discountValue);
                        if (this.discountType === 'percentage') {
                            return amt * (dVal / 100);
                        } else {
                            return dVal;
                        }
                    },
                    finalPayableAmount() {
                        let amt = parseFloat(this.formData.amount) || 0;
                        let discount = this.calculatedDiscountAmount();
                        return Math.max(0, amt - discount).toFixed(2);
                    },

                    init() {
                        try {
                            window.addEventListener('revenue-category-created', (e) => {
                                try {
                                    const cat = e?.detail;
                                    if (!cat || !cat.id) return;
                                    if (!Array.isArray(this.categories)) this.categories = [];
                                    this.categories.push(cat);
                                    this.formData.category_id = String(cat.id);
                                    this.showCategoryModal = false;
                                    this.updateSummary();
                                    this.updateAllocationPreview();
                                } catch (err) {
                                    console.error('Error handling revenue-category-created:', err);
                                }
                            });
                            this.updateSummary();

                            // Listen for student selection
                            if (this.$el) {
                                this.$el.addEventListener('student-selected', (e) => {
                                    try {
                                        if (e && e.detail) {
                                            this.studentName = e.detail.name || '';
                                            this.studentDueAmount = e.detail.due_amount || 0;
                                            this.studentDueMonths = e.detail.due_months || [];
                                            this.studentMonthlyFee = e.detail.monthly_fee || 0;
                                            this.studentHoldAmount = e.detail.hold_amount || 0;
                                            this.studentHoldChequeNumbers = Array.isArray(e.detail.hold_cheque_numbers) ? e.detail.hold_cheque_numbers : [];
                                            this.selectedStudentId = e.detail.id || '';
                                            this.monthlyCatId = e.detail.monthly_category_id || '';
                                            this.yearlyFeeRows = this.rowsFromDueMonths(this.studentDueMonths);

                                            // monthlyCatId becomes known only after student selection,
                                            // so re-evaluate whether the selected category is the student's monthly fee category.
                                            this.updateSummary();
                                            
                                            const adv = Array.isArray(e.detail.advance_months) ? e.detail.advance_months : [];
                                            if (adv.length > 0) {
                                                this.advanceOptions = adv.map(m => ({
                                                    key: m.key,
                                                    year: m.year,
                                                    month: m.month,
                                                    label: m.label,
                                                    required_amount: Number(m.required_amount ?? 0),
                                                }));
                                            } else {
                                                this.initFutureMonths();
                                                this.advanceOptions = this.futureMonths;
                                            }
                                            this.updateAllocationPreview();
                                        } else {
                                            this.studentName = '';
                                            this.studentDueAmount = 0;
                                            this.studentDueMonths = [];
                                            this.yearlyFeeRows = [];
                                            this.studentHoldAmount = 0;
                                            this.studentHoldChequeNumbers = [];
                                            this.selectedStudentId = '';
                                            this.monthlyCatId = '';
                                            this.advanceMode = false;
                                            this.selectedAdvanceKeys.clear();
                                            this.syncAdvanceSelections();
                                            this.allocation = { allocations: [], summary: { total_applied: 0, unallocated_balance: 0, paid_due_months: [], advance_months: [], errors: [] } };
                                        }
                                    } catch (err) {
                                        console.error('Error in student-selected handler:', err);
                                    }
                                });
                            }
                            this.initFutureMonths();
                            this.advanceOptions = this.futureMonths;
                            try {
                                this.$watch('advanceMode', (val) => {
                                    this.advanceEnabled = !!val;
                                    if (!val) {
                                        this.selectedAdvanceKeys.clear();
                                        this.syncAdvanceSelections();
                                        this.updateAllocationPreview();
                                    }
                                });
                            } catch (e) {}
                            this.updateAllocationPreview();
                        } catch (err) {
                            console.error('Error initializing revenueForm:', err);
                        }
                    },

                    allocationFlowText() {
                        const allocs = Array.isArray(this.allocation?.allocations) ? this.allocation.allocations : [];
                        if (!allocs.length) return '';

                        const parts = allocs.map(a => {
                            if (!a || !a.year || !a.month) return null;
                            const label = `${this.shortMonthName(a.month)} ${a.year}`;
                            const type = a.type === 'due' ? 'due' : (a.type === 'advance' ? 'advance' : '');
                            if (a.is_partial) {
                                return `${label} (${type}, partial Rs ${this.formatMoney(a.applied_amount)}; balance Rs ${this.formatMoney(a.remaining_for_month)})`;
                            }
                            return `${label} (${type}, full Rs ${this.formatMoney(a.applied_amount)})`;
                        }).filter(Boolean);

                        return parts.length ? `Allocation: ${parts.join(', ')}` : '';
                    },

                    updateSummary() {
                        try {
                            const id = this.formData.category_id;
                            const cat = Array.isArray(this.categories) ? this.categories.find(c => c.id == id) : null;
                            if (cat) {
                                this.categoryName = cat.name;
                                const rawType = (cat.payment_type || '').toString();
                                const monthlyLike = ['monthly','2_months','3_months','6_months','yearly','custom_months'].includes(rawType);
                                this.categoryType = monthlyLike ? 'monthly' : rawType;
                                // If class-wise monthly category is not configured, allow selected monthly category.
                                this.selectedCategoryIsMonthlyFee = monthlyLike && (!this.monthlyCatId || String(this.monthlyCatId) === String(cat.id));
                            } else {
                                this.categoryName = '';
                                this.categoryType = '';
                                this.selectedCategoryIsMonthlyFee = false;
                            }
                        } catch (err) {
                            console.error('Error updating summary:', err);
                        }
                    },
                    initFutureMonths() {
                        const base = new Date();
                        base.setDate(1);
                        this.futureMonths = [];
                        for (let i = 0; i < 12; i++) {
                            const d = new Date(base.getFullYear(), base.getMonth() + i, 1);
                            const key = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
                            this.futureMonths.push({ key, year: d.getFullYear(), month: d.getMonth()+1, label: `${this.monthName(d.getMonth()+1)} ${d.getFullYear()}` });
                        }
                    },
                    toggleAdvanceMonth(m, label) {
                        if (!this.advanceEnabled) return;
                        if (Array.isArray(this.studentDueMonths) && this.studentDueMonths.length > 0) {
                            alert('Cannot select advance months while there are dues pending.');
                            return;
                        }
                        let key = m;
                        if (typeof m === 'object' && m) key = m.key;
                        if (typeof key !== 'string') return;
                        if (this.selectedAdvanceKeys.has(key)) {
                            this.selectedAdvanceKeys.delete(key);
                        } else {
                            this.selectedAdvanceKeys.add(key);
                            const ordered = [...(this.advanceOptions||[]).map(f=>f.key)].filter(k=>this.selectedAdvanceKeys.has(k));
                            this.selectedAdvanceKeys = new Set(ordered);
                        }
                        this.syncAdvanceSelections();
                        this.updateAllocationPreview();
                    },
                    isAdvanceSelected(key) {
                        return this.selectedAdvanceKeys.has(key);
                    },
                    syncAdvanceSelections() {
                        const keys = [...this.selectedAdvanceKeys];
                        this.selectedAdvanceMonths = keys;
                        const map = new Map((this.advanceOptions||[]).map(o => [o.key, o.label]));
                        this.selectedAdvanceLabels = keys.map(k => map.get(k)).filter(Boolean);
                    },
                    advanceRequiredAmount() {
                        const keys = [...this.selectedAdvanceKeys];
                        if (!keys.length) return 0;

                        const map = new Map((this.advanceOptions||[]).map(o => [o.key, o.required_amount]));
                        let sum = 0;
                        let allHave = true;
                        for (const k of keys) {
                            const v = map.get(k);
                            if (typeof v !== 'number' || Number.isNaN(v)) {
                                allHave = false;
                                break;
                            }
                            sum += Number(v);
                        }
                        if (allHave) return sum;

                        const fromPreview = Number(this.allocation?.summary?.selected_advance_months_required_amount);
                        if (!Number.isNaN(fromPreview)) return fromPreview;

                        return keys.length * (Number(this.studentMonthlyFee) || 0);
                    },
                    monthName(m) {
                        const names = ['January','February','March','April','May','June','July','August','September','October','November','December'];
                        return names[(m-1)%12];
                    },
                    shortMonthName(m) {
                        return this.monthName(m).slice(0, 3);
                    },
                    formatMoney(v) {
                        const n = Number(v || 0);
                        return n.toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    },
                    rowsFromDueMonths(months) {
                        if (!Array.isArray(months)) return [];
                        const byYear = new Map();
                        for (const month of months) {
                            const key = String(month?.month_key || '');
                            const [year, mo] = key.split('-').map(v => Number(v));
                            if (!year || !mo) continue;
                            if (!byYear.has(year)) {
                                byYear.set(year, Number(month?.due_amount ?? month?.amount ?? 0));
                            }
                        }

                        return [...byYear.entries()].map(([year, feeAmount]) => ({
                            uid: ++this.yearlyFeeRowSeq,
                            year,
                            fee_amount: Number(feeAmount || 0).toFixed(2),
                        }));
                    },
                    addYearlyFeeRow() {
                        const today = new Date();
                        this.yearlyFeeRows.push({
                            uid: ++this.yearlyFeeRowSeq,
                            year: today.getFullYear(),
                            fee_amount: Number(this.studentMonthlyFee || 0).toFixed(2),
                        });
                        this.yearlyFeeRowsChanged();
                    },
                    removeYearlyFeeRow(index) {
                        this.yearlyFeeRows.splice(index, 1);
                        this.yearlyFeeRowsChanged();
                    },
                    yearlyFeeRowsPayload() {
                        const byKey = new Map();
                        for (const row of this.yearlyFeeRows || []) {
                            const year = Number(row.year || 0);
                            const fee = Number(row.fee_amount || 0);
                            if (year < 2000 || fee <= 0) continue;
                            for (let month = 1; month <= 12; month++) {
                                byKey.set(`${year}-${String(month).padStart(2, '0')}`, {
                                    year,
                                    month,
                                    fee_amount: Number(fee.toFixed(2)),
                                });
                            }
                        }
                        return [...byKey.values()].sort((a, b) => (a.year * 100 + a.month) - (b.year * 100 + b.month));
                    },
                    yearlyFeeRowsPayableTotal() {
                        const feeByYear = new Map();
                        for (const row of this.yearlyFeeRows || []) {
                            const year = Number(row.year || 0);
                            const fee = Number(row.fee_amount || 0);
                            if (year >= 2000 && fee > 0) {
                                feeByYear.set(year, fee);
                            }
                        }

                        const paidByKey = new Map((this.studentDueMonths || []).map((month) => [
                            String(month?.month_key || ''),
                            Number(month?.paid_amount || 0),
                        ]));

                        return (this.studentDueMonths || []).reduce((sum, month) => {
                            const key = String(month?.month_key || '');
                            const [year] = key.split('-').map(v => Number(v));
                            const fee = feeByYear.get(year);
                            if (!fee) return sum;
                            return sum + Math.max(0, fee - Number(paidByKey.get(key) || 0));
                        }, 0);
                    },
                    setAmountFromYearlyFeeRows() {
                        this.formData.amount = this.yearlyFeeRowsPayableTotal().toFixed(2);
                        this.updateAllocationPreview();
                    },
                    yearlyFeeRowsChanged() {
                        window.clearTimeout(this.yearlyFeePreviewTimer);
                        this.yearlyFeePreviewTimer = window.setTimeout(() => this.updateAllocationPreview(), 250);
                    },
                    coverageSummaryText() {
                        const allocs = Array.isArray(this.allocation?.allocations) ? this.allocation.allocations : [];
                        if (!allocs.length) return '—';

                        const seen = new Set();
                        const parts = [];
                        for (const a of allocs) {
                            if (!a || !a.year || !a.month) continue;
                            const key = `${a.year}-${String(a.month).padStart(2,'0')}`;
                            if (seen.has(key)) continue;
                            seen.add(key);

                            const label = `${this.shortMonthName(a.month)} ${a.year}`;
                            const typeLabel = a.type === 'due' ? 'due' : (a.type === 'advance' ? 'advance' : '');
                            if (a.is_partial) {
                                const bal = this.formatMoney(a.remaining_for_month || 0);
                                parts.push(`${label} (${typeLabel}${typeLabel ? ', ' : ''}partial Rs ${bal} balance)`);
                            } else {
                                parts.push(`${label} (${typeLabel}${typeLabel ? ', ' : ''}full)`);
                            }
                        }

                        return parts.length ? parts.join(', ') : '—';
                    },
                    async updateAllocationPreview() {
                        try {
                            if (this.categoryType !== 'monthly' || !this.selectedCategoryIsMonthlyFee) return;
                            const amt = parseFloat(this.formData.amount||'0');
                            if (!this.selectedStudentId || !amt || amt <= 0) {
                                this.allocation = { allocations: [], summary: { total_applied: 0, unallocated_balance: 0, paid_due_months: [], advance_months: [], errors: [] } };
                                return;
                            }

                            this.isAllocationLoading = true;
                            const adv = [...this.selectedAdvanceKeys].map(k => { const [y, mo] = k.split('-'); return {year: +y, month: +mo}; });
                            const feeOverrides = this.yearlyFeeRowsPayload();
                            const payload = { student_id: this.selectedStudentId, revenue_category_id: this.formData.category_id, amount: amt, advance_months: adv, monthly_fee_overrides: feeOverrides };
                            if (this.editingRevenueId) payload.revenue_id = this.editingRevenueId;
                            const res = await fetch("{{ route('revenue.items.preview_allocation') }}", {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '' },
                                body: JSON.stringify(payload)
                            });
                            if (res.ok) {
                                const data = await res.json();
                                this.allocation = data || { allocations: [], summary: {} };
                                if (this.advanceEnabled && this.selectedAdvanceKeys.size === 0) {
                                    // Auto-select future months based on remaining amount
                                    const rem = Number(this.allocation.summary.unallocated_balance||0);
                                    const fee = Number(this.studentMonthlyFee||0);
                                    if (fee > 0 && rem > 0) {
                                        const fullCount = Math.floor(rem / fee);
                                        const extra = rem - fullCount*fee;
                                        const selectCount = fullCount + (extra > 0 ? 1 : 0);
                                        const keys = (this.advanceOptions||this.futureMonths).slice(0, selectCount).map(m=>m.key);
                                        this.selectedAdvanceKeys = new Set(keys);
                                        this.syncAdvanceSelections();
                                        // Recompute preview with auto-selected months
                                        const adv2 = [...this.selectedAdvanceKeys].map(k => { const [y, mo] = k.split('-'); return {year: +y, month: +mo}; });
                                        const payload2 = { student_id: this.selectedStudentId, revenue_category_id: this.formData.category_id, amount: amt, advance_months: adv2, monthly_fee_overrides: feeOverrides };
                                        if (this.editingRevenueId) payload2.revenue_id = this.editingRevenueId;
                                        const res2 = await fetch("{{ route('revenue.items.preview_allocation') }}", {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '' },
                                            body: JSON.stringify(payload2)
                                        });
                                        if (res2.ok) {
                                            const data2 = await res2.json();
                                            this.allocation = data2;
                                        }
                                    }
                                }
                            }
                        } catch (e) {
                            console.warn('Allocation preview failed:', e?.message);
                        } finally {
                            this.isAllocationLoading = false;
                        }
                    }
                }));

                // AJAX create category from modal (stay on Add Revenue page)
                window.addEventListener('DOMContentLoaded', () => {
                    const form = document.getElementById('add-category-form');
                    if (!form) return;

                    form.addEventListener('submit', async (e) => {
                        e.preventDefault();
                        const errBox = document.getElementById('add-category-error');
                        if (errBox) {
                            errBox.classList.add('hidden');
                            errBox.textContent = '';
                        }

                        const fd = new FormData(form);
                        // Ensure controller validation passes for modal (applies_to_all is required)
                        if (!fd.has('applies_to_all')) fd.set('applies_to_all', '1');

                        try {
                            const res = await fetch(form.action, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || ''
                                },
                                body: fd,
                            });

                            if (!res.ok) {
                                const data = await res.json().catch(() => null);
                                const msg = data?.message || 'Could not create category.';
                                const firstErr = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                                if (errBox) {
                                    errBox.textContent = firstErr || msg;
                                    errBox.classList.remove('hidden');
                                }
                                return;
                            }

                            const cat = await res.json();
                            // Update the dropdown options immediately
                            const sel = document.getElementById('revenue_category_id');
                            if (sel && cat?.id) {
                                const opt = document.createElement('option');
                                opt.value = String(cat.id);
                                opt.textContent = cat.name;
                                opt.setAttribute('data-name', cat.name);
                                opt.setAttribute('data-type', cat.payment_type || 'one_time');
                                sel.appendChild(opt);
                                sel.value = String(cat.id);
                                sel.dispatchEvent(new Event('change', { bubbles: true }));
                            }
                            window.dispatchEvent(new CustomEvent('revenue-category-created', { detail: cat }));
                            form.reset();
                        } catch (ex) {
                            if (errBox) {
                                errBox.textContent = 'Network error while creating category.';
                                errBox.classList.remove('hidden');
                            }
                        }
                    });
                });

                Alpine.data('studentPicker', () => ({
                    q: '',
                    classRoomId: '',
                    results: [],
                    open: false,
                    selected: null,
                    highlightedIndex: -1,
                    initialStudentId: '',
                    excludeRevenueId: '',
                    isLoading: false,

                    init() {
                        try {
                            this.initialStudentId = this.$el?.getAttribute('data-student-id') || '';
                            this.excludeRevenueId = this.$el?.getAttribute('data-exclude-revenue-id') || '';
                            if (this.initialStudentId) {
                                this.loadInitialStudent();
                            }
                            this.stripStudentParam();
                        } catch (err) {
                            console.error('Error initializing studentPicker:', err);
                        }
                    },

                    async loadInitialStudent() {
                        if (!this.initialStudentId) return;
                        try {
                            this.isLoading = true;
                            const excludeParam = this.excludeRevenueId ? `&exclude_revenue_id=${encodeURIComponent(this.excludeRevenueId)}` : '';
                            const res = await fetch(`/students/search?id=${encodeURIComponent(this.initialStudentId)}${excludeParam}`);
                            if (res && res.ok) {
                                const data = await res.json();
                                if (data) {
                                    const payload = Array.isArray(data) ? data : (data?.results ?? []);
                                    if (Array.isArray(payload) && payload.length > 0) {
                                        const first = payload[0];
                                        if (first && first.id) {
                                            this.selected = first;
                                            try {
                                                this.$dispatch('student-selected', this.selected);
                                            } catch (dispatchErr) {
                                                console.error('Error dispatching student-selected event:', dispatchErr);
                                            }
                                        }
                                    }
                                }
                            }
                        } catch (e) {
                            console.warn('Failed to load initial student (non-critical):', e?.message);
                        } finally {
                            this.isLoading = false;
                        }
                    },

                    async search() {
                        try {
                            if (!this.q || typeof this.q.trim !== 'function') {
                                this.results = [];
                                this.open = false;
                                return;
                            }
                            if (!this.q.trim()) {
                                this.results = [];
                                this.open = false;
                                return;
                            }
                            const params = new URLSearchParams({ q: this.q, limit: 10 });
                            if (this.classRoomId) params.set('class_room_id', this.classRoomId);
                            this.isLoading = true;
                            const res = await fetch(`/students/search?${params}`);
                            if (res && res.ok) {
                                const data = await res.json();
                                if (data) {
                                    this.results = Array.isArray(data) ? data : (data?.results ?? []);
                                    if (!Array.isArray(this.results)) {
                                        this.results = [];
                                    }
                                    this.open = this.results.length > 0;
                                    this.highlightedIndex = -1;
                                }
                            }
                        } catch (e) {
                            console.warn('Student search error (non-critical):', e?.message);
                            this.results = [];
                            this.open = false;
                        } finally {
                            this.isLoading = false;
                        }
                    },

                    async openDefault() {
                        try {
                            const params = new URLSearchParams({ limit: 5 });
                            if (this.classRoomId) params.set('class_room_id', this.classRoomId);
                            this.isLoading = true;
                            const res = await fetch(`/students/search?${params}`);
                            if (res && res.ok) {
                                const data = await res.json();
                                if (data) {
                                    this.results = Array.isArray(data) ? data : (data?.results ?? []);
                                    if (!Array.isArray(this.results)) {
                                        this.results = [];
                                    }
                                    this.open = this.results.length > 0;
                                    this.highlightedIndex = -1;
                                }
                            }
                        } catch (e) {
                            console.warn('Default student load error (non-critical):', e?.message);
                            this.results = [];
                            this.open = false;
                        } finally {
                            this.isLoading = false;
                        }
                    },

                    async select(item) {
                        try {
                            if (!item || typeof item !== 'object') return;
                            
                            // Fetch full details to get due info
                            try {
                                this.isLoading = true;
                                const res = await fetch(`/students/search?id=${item.id}`);
                                if (res && res.ok) {
                                    const data = await res.json();
                                    if (data.results && data.results[0]) {
                                        item = data.results[0];
                                    }
                                }
                            } catch (fetchErr) {
                                console.warn('Failed to fetch full student details:', fetchErr);
                            } finally {
                                this.isLoading = false;
                            }

                            this.selected = item;
                            this.q = '';
                            this.open = false;
                            this.results = [];
                            this.highlightedIndex = -1;
                            
                            this.$dispatch('student-selected', this.selected);
                        } catch (err) {
                            console.error('Error selecting student:', err);
                        }
                    },

                    clearSelection() {
                        try {
                            this.selected = null;
                            this.$dispatch('student-selected', null);
                        } catch (err) {
                            console.error('Error clearing selection:', err);
                        }
                    },

                    handleKeyDown(event) {
                        try {
                            if (!this.open || !Array.isArray(this.results) || this.results.length === 0) return;
                            if (event && event.key === 'ArrowDown') {
                                event.preventDefault();
                                this.highlightedIndex = Math.min(this.highlightedIndex + 1, this.results.length - 1);
                            } else if (event && event.key === 'ArrowUp') {
                                event.preventDefault();
                                this.highlightedIndex = Math.max(this.highlightedIndex - 1, -1);
                            } else if (event && event.key === 'Enter' && this.highlightedIndex >= 0) {
                                event.preventDefault();
                                const item = this.results[this.highlightedIndex];
                                if (item) {
                                    this.select(item);
                                    try {
                                        this.$dispatch('student-selected', item);
                                    } catch (dispatchErr) {
                                        console.error('Error dispatching student selection:', dispatchErr);
                                    }
                                }
                            } else if (event && event.key === 'Escape') {
                                this.open = false;
                                this.highlightedIndex = -1;
                            }
                        } catch (err) {
                            console.error('Error in handleKeyDown:', err);
                        }
                    },

                    stripStudentParam() {
                        try {
                            const url = new URL(window.location);
                            if (url.searchParams.has('student_id')) {
                                url.searchParams.delete('student_id');
                                history.replaceState({}, '', url);
                            }
                        } catch (err) {
                            console.warn('Could not strip student param (non-critical):', err?.message);
                        }
                    }
                }));

            });
        </script>
    @endpush
</x-app-layout>
