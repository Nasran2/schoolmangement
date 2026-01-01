<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-900 leading-tight">Add Revenue</h2>
                <p class="mt-1 text-sm text-gray-600">Complete your payment transaction</p>
            </div>
            <a href="{{ route('revenue.items.index') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
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
                            
                            <form method="POST" action="{{ route('revenue.items.store') }}" class="space-y-7" x-ref="paymentForm">
                                @csrf

                                {{-- Category with Add Button --}}
                                <div x-data="{ showCategoryModal: false }">
                                    <label class="block text-sm font-semibold text-gray-800 mb-3">Payment Category</label>
                                    <div class="flex gap-2">
                                        <div class="flex-1">
                                            <select id="revenue_category_id" name="revenue_category_id" class="block w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all" x-model="formData.category_id" x-on:change="updateSummary()">
                                                <option value="">Select Category</option>
                                                @foreach ($categories as $cat)
                                                    <option value="{{ $cat->id }}" data-name="{{ $cat->name }}" data-type="{{ $cat->payment_type }}" {{ ($preselectedCategoryId && $preselectedCategoryId === $cat->id) ? 'selected' : '' }}>{{ $cat->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="button" x-on:click="showCategoryModal = true" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-lg hover:from-indigo-600 hover:to-indigo-700 shadow-sm transition-all font-medium" title="Add new category">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            Add
                                        </button>
                                    </div>
                                    
                                    <div x-show="categoryType" x-cloak class="mt-3">
                                        <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-semibold" :class="categoryType === 'monthly' ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700'">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" class="w-3 h-3">
                                                <path d="M9.195 18.44c.059.468.076.96.076 1.456 0 1.193-.668 2.326-1.634 2.653C5.503 23.008 2 20.7 2 17.72c0-1.229.756-2.291 1.823-2.775 1.017 2.484 3.582 4.479 6.372 4.495z"/><path d="M9 6a4 4 0 100-8 4 4 0 000 8zm15 0a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                            <span x-text="categoryType.charAt(0).toUpperCase() + categoryType.slice(1) + ' Fee'"></span>
                                        </span>
                                    </div>
                                    @error('revenue_category_id')
                                        <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                    @enderror

                                    {{-- Add Category Modal --}}
                                    <div x-show="showCategoryModal" x-cloak class="fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center" x-on:click.self="showCategoryModal = false">
                                        <div class="bg-white w-full sm:max-w-md sm:rounded-2xl rounded-t-2xl shadow-2xl p-6 sm:p-8 animate-in fade-in slide-in-from-bottom-4 duration-300" @click.stop>
                                            <div class="flex items-center justify-between mb-6">
                                                <h4 class="text-xl font-bold text-gray-900">Add New Category</h4>
                                                <button type="button" x-on:click="showCategoryModal = false" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-gray-500">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                            
                                            <form id="add-category-form" x-data="{ name: '', type: 'other', loading: false }" x-on:submit.prevent="addCategory()" class="space-y-5">
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-800 mb-2">Category Name</label>
                                                    <input type="text" x-model="name" placeholder="e.g., Tuition Fee, Registration" class="block w-full px-4 py-2.5 border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all" required>
                                                </div>
                                                
                                                <div>
                                                    <label class="block text-sm font-semibold text-gray-800 mb-2">Payment Type</label>
                                                    <select x-model="type" class="block w-full px-4 py-2.5 border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all">
                                                        <option value="other">One-time</option>
                                                        <option value="monthly">Monthly</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="flex gap-3 pt-4">
                                                    <button type="button" x-on:click="showCategoryModal = false" class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold transition-colors">
                                                        Cancel
                                                    </button>
                                                    <button type="submit" :disabled="loading" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-lg hover:from-indigo-600 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed font-semibold transition-all">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                                        </svg>
                                                        <span x-text="loading ? 'Adding...' : 'Add Category'"></span>
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                {{-- Student Picker --}}
                                <div x-data="studentPicker()" x-init="init()" data-student-id="{{ $selectedStudentId ?? '' }}">
                                    <label class="block text-sm font-semibold text-gray-800 mb-3">Student <span class="font-normal text-gray-500">(optional)</span></label>
                                    
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                        <div class="sm:col-span-2">
                                            <div class="relative">
                                                <input type="text" class="block w-full px-4 py-2.5 rounded-lg border-gray-300 pr-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all" placeholder="Search by name, admission no, phone" x-model="q" x-on:input.debounce.300ms="search()" x-on:keydown="handleKeyDown($event)">
                                                <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600" x-on:click="openDefault()">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg>
                                                </button>
                                                <div x-show="open" x-cloak class="absolute z-20 mt-2 w-full rounded-lg border border-gray-200 bg-white shadow-xl max-h-64 overflow-auto">
                                                    <template x-if="results.length === 0">
                                                        <div class="px-4 py-4 text-sm text-gray-600 text-center">No matches found.</div>
                                                    </template>
                                                    <template x-for="(item, idx) in results" :key="item.id">
                                                        <button type="button" class="flex w-full items-center justify-between px-4 py-3.5 text-left transition-colors border-b border-gray-100 last:border-0" :class="highlightedIndex === idx ? 'bg-indigo-50' : 'hover:bg-gray-50'" x-on:click="select(item); $dispatch('student-selected', item)">
                                                            <div class="min-w-0 flex-1">
                                                                <div class="text-sm font-semibold text-gray-900 truncate" x-text="item.name"></div>
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
                                            <select class="block w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all" x-model="classRoomId" x-on:change="search()">
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

                                    <div x-show="selected" x-cloak class="mt-4 rounded-xl border-2 border-indigo-500 bg-gradient-to-r from-indigo-50 to-blue-50 p-5">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-bold text-indigo-900" x-text="selected?.name"></p>
                                                <p class="text-xs text-indigo-700 mt-2">
                                                    <span class="inline-block px-2 py-1 bg-indigo-100 rounded">Class: <span x-text="selected?.class"></span></span>
                                                </p>
                                                <p class="text-xs text-indigo-700 mt-1">
                                                    <span class="inline-block px-2 py-1 bg-indigo-100 rounded">ID: <span x-text="selected?.admission_number"></span></span>
                                                </p>
                                            </div>
                                            <button type="button" class="p-2 hover:bg-indigo-200 rounded-lg transition-colors flex-shrink-0" x-on:click="clearSelection()">
                                                <svg class="w-5 h-5 text-indigo-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                {{-- Amount and Date --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800 mb-3">Amount</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-600 font-semibold">Rs</span>
                                            <input type="number" name="amount" step="0.01" min="0.01" class="block w-full pl-12 pr-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all" placeholder="0.00" value="{{ old('amount') }}" x-model="formData.amount" x-on:input="updateSummary()" required>
                                        </div>
                                        @error('amount')
                                            <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800 mb-3">Payment Date</label>
                                        <input type="date" name="paid_at" class="block w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all" value="{{ old('paid_at', date('Y-m-d')) }}" x-model="formData.date" required>
                                        @error('paid_at')
                                            <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Bill Number --}}
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-3">Bill Number <span class="font-normal text-gray-500">(optional)</span></label>
                                    <input type="text" name="bill_no" class="block w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all" placeholder="Auto-generate if empty" value="{{ old('bill_no') }}" x-model="formData.bill_no">
                                    <p class="mt-2 text-xs text-gray-500">Leave blank to auto-generate</p>
                                    @error('bill_no')
                                        <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Notes --}}
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-3">Notes <span class="font-normal text-gray-500">(optional)</span></label>
                                    <textarea name="notes" rows="3" class="block w-full px-4 py-2.5 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 focus:ring-2 transition-all" placeholder="Add any additional notes...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <p class="mt-2 text-sm text-red-600 font-medium">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- Submit Buttons --}}
                                <div class="flex items-center gap-4 pt-8 border-t border-gray-200">
                                    <button type="submit" class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-indigo-600 to-indigo-700 border border-transparent rounded-xl font-semibold text-white shadow-lg hover:from-indigo-700 hover:to-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all hover:shadow-xl">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Save Payment
                                    </button>
                                    <a href="{{ route('revenue.items.index') }}" class="px-6 py-3 bg-gray-100 border border-gray-200 rounded-xl font-semibold text-gray-700 shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 focus:ring-offset-2 transition-colors">
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
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-indigo-600">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .984.575 1.83 1.407 2.228.62.56 1.405.949 2.493.949.882 0 1.642-.949 1.738-1.813.032-.268.06-.56.06-.856 0-.331-.035-.624-.1-.864m0 0C9.806 2.852 9.426 2.25 8.25 2.25 7.065 2.25 6 3.494 6 5.008c0 1.518 1.062 2.813 2.258 2.813.859 0 1.579-.597 1.681-1.438m0 0M16.5 12.75h.008v.008h-.008v-.008z" />
                                </svg>
                                Billing Summary
                            </h3>
                            
                            <div class="space-y-4 mb-8">
                                <div class="flex justify-between items-start gap-3 pb-4 border-b border-gray-100">
                                    <span class="text-sm font-medium text-gray-600">Category:</span>
                                    <span class="text-sm font-semibold text-gray-900 text-right" x-text="categoryName || '—'"></span>
                                </div>
                                <div class="flex justify-between items-start gap-3 pb-4 border-b border-gray-100">
                                    <span class="text-sm font-medium text-gray-600">Student:</span>
                                    <span class="text-sm font-semibold text-gray-900 text-right" x-text="studentName || '—'"></span>
                                </div>
                                <div class="flex justify-between items-start gap-3 pb-4 border-b border-gray-100">
                                    <span class="text-sm font-medium text-gray-600">Date:</span>
                                    <span class="text-sm font-semibold text-gray-900 text-right" x-text="formData.date || '—'"></span>
                                </div>
                            </div>

                            <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-5 mb-6 border border-indigo-200">
                                <div class="text-center">
                                    <p class="text-xs uppercase tracking-wide font-semibold text-indigo-700 mb-1">Total Amount</p>
                                    <div class="flex items-baseline justify-center gap-1">
                                        <span class="text-sm font-medium text-indigo-700">Rs</span>
                                        <span class="text-3xl font-bold text-indigo-600" x-text="Number(formData.amount || 0).toLocaleString('en', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                                <div class="flex gap-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                    </svg>
                                    <div class="text-sm text-blue-900">
                                        <p class="font-semibold mb-1">Receipt Generation</p>
                                        <p class="text-xs text-blue-800">Receipt will be generated after saving. You can print it from settings.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Expose helpers globally so Alpine can always find them
        window.revenueForm = function revenueForm() {
            return {
                formData: {
                    category_id: '{{ old('revenue_category_id', $preselectedCategoryId) }}',
                    amount: '{{ old('amount') }}',
                    date: '{{ old('paid_at', date('Y-m-d')) }}',
                    bill_no: '{{ old('bill_no') }}'
                },
                categoryName: '',
                categoryType: '',
                studentName: '',
                
                init() {
                    this.updateSummary();
                    
                    // Listen for student selection
                    this.$el.addEventListener('student-selected', (e) => {
                        this.studentName = e.detail.name;
                    });
                },
                
                updateSummary() {
                    const categorySelect = document.getElementById('revenue_category_id');
                    const selectedOption = categorySelect?.options[categorySelect.selectedIndex];
                    this.categoryName = selectedOption?.getAttribute('data-name') || '-';
                    this.categoryType = selectedOption?.getAttribute('data-type') || '';
                }
            }
        }

        window.studentPicker = function studentPicker() {
            return {
                q: '',
                classRoomId: '',
                results: [],
                open: false,
                selected: null,
                highlightedIndex: -1,
                initialStudentId: '',

                init() {
                    this.initialStudentId = this.$el.getAttribute('data-student-id') || '';
                    if (this.initialStudentId) {
                        this.loadInitialStudent();
                    }
                    this.stripStudentParam();
                },

                async loadInitialStudent() {
                    if (!this.initialStudentId) return;
                    try {
                        const res = await fetch(`/students/search?id=${encodeURIComponent(this.initialStudentId)}`);
                        if (res.ok) {
                            const data = await res.json();
                            const payload = Array.isArray(data) ? data : (data?.results ?? []);
                            const first = payload?.[0];
                            if (first) {
                                this.selected = first;
                                this.$dispatch('student-selected', this.selected);
                            }
                        }
                    } catch (e) {
                        console.error('Failed to load initial student', e);
                    }
                },

                async search() {
                    if (!this.q.trim()) {
                        this.results = [];
                        this.open = false;
                        return;
                    }
                    const params = new URLSearchParams({ q: this.q, limit: 10 });
                    if (this.classRoomId) params.set('class_room_id', this.classRoomId);
                    try {
                        const res = await fetch(`/students/search?${params}`);
                        if (res.ok) {
                            const data = await res.json();
                            this.results = Array.isArray(data) ? data : (data?.results ?? []);
                            this.open = true;
                            this.highlightedIndex = -1;
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                async openDefault() {
                    const params = new URLSearchParams({ limit: 5 });
                    if (this.classRoomId) params.set('class_room_id', this.classRoomId);
                    try {
                        const res = await fetch(`/students/search?${params}`);
                        if (res.ok) {
                            const data = await res.json();
                            this.results = Array.isArray(data) ? data : (data?.results ?? []);
                            this.open = true;
                            this.highlightedIndex = -1;
                        }
                    } catch (e) {
                        console.error(e);
                    }
                },

                select(item) {
                    this.selected = item;
                    this.q = '';
                    this.open = false;
                    this.results = [];
                    this.highlightedIndex = -1;
                },

                clearSelection() {
                    this.selected = null;
                    this.$dispatch('student-selected', null);
                },

                handleKeyDown(event) {
                    if (!this.open || this.results.length === 0) return;
                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        this.highlightedIndex = Math.min(this.highlightedIndex + 1, this.results.length - 1);
                    } else if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        this.highlightedIndex = Math.max(this.highlightedIndex - 1, -1);
                    } else if (event.key === 'Enter' && this.highlightedIndex >= 0) {
                        event.preventDefault();
                        this.select(this.results[this.highlightedIndex]);
                        this.$dispatch('student-selected', this.results[this.highlightedIndex]);
                    } else if (event.key === 'Escape') {
                        this.open = false;
                        this.highlightedIndex = -1;
                    }
                },

                stripStudentParam() {
                    const url = new URL(window.location);
                    if (url.searchParams.has('student_id')) {
                        url.searchParams.delete('student_id');
                        history.replaceState({}, '', url);
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
