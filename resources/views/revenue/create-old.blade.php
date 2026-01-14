<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Revenue</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('revenue.items.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <div class="flex items-center justify-between">
                                <x-input-label for="revenue_category_id" :value="__('Category')" />
                                @if(!empty($monthlyCatId))
                                    <button type="button" id="quickMonthlyBtn" class="inline-flex items-center gap-1 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow hover:bg-indigo-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                            <path d="M7 11h10v2H7z"/><path d="M12 2a10 10 0 1 1 0 20 10 10 0 0 1 0-20z" fill-opacity=".1"/>
                                        </svg>
                                        Apply Monthly Fee
                                    </button>
                                @endif
                            </div>
                            <select id="revenue_category_id" name="revenue_category_id" class="mt-1 block w-full rounded-md border-gray-300">
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('revenue_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <div class="mt-2 text-xs">
                                <span id="categoryTypeInfo" class="inline-flex items-center rounded px-2 py-0.5 font-medium"></span>
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('revenue_category_id')" />
                        </div>

                        <div x-data="studentPicker()" x-init="init()" data-student-id="{{ $selectedStudentId ?? '' }}">
                            <x-input-label for="student_search" :value="__('Student (optional)')" />

                            <div class="mt-1 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                <div class="sm:col-span-2">
                                    <div class="relative">
                                        <input id="student_search" type="text" class="block w-full rounded-md border-gray-300 pr-10" placeholder="Search by admission no, name, phone" x-model="q" x-on:input.debounce.300ms="search()">
                                        <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400" x-on:click="openDefault()" aria-label="Search">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg>
                                        </button>
                                        <div x-show="open" x-cloak class="absolute z-10 mt-1 w-full rounded-md border bg-white shadow">
                                            <template x-if="results.length === 0">
                                                <div class="px-3 py-2 text-sm text-gray-600">No matches.</div>
                                            </template>
                                            <template x-for="(item, idx) in results" :key="item.id">
                                                <button type="button" class="flex w-full items-center justify-between px-3 py-2 text-left" :class="highlightedIndex === idx ? 'bg-indigo-100' : 'hover:bg-gray-50'" x-on:click="select(item)">
                                                    <div class="min-w-0">
                                                        <div class="text-sm font-medium text-gray-900 truncate" x-text="item.name"></div>
                                                        <div class="text-xs text-gray-600 truncate"><span x-text="item.class"></span> · <span x-text="item.admission_number"></span> · <span x-text="item.phone || ''"></span></div>
                                                    </div>
                                                    <span class="text-xs text-gray-500">ID: <span x-text="item.id"></span></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <select class="block w-full rounded-md border-gray-300" x-model="classRoomId" x-on:change="search()">
                                        <option value="">All Classes</option>
                                        @foreach ($classRooms as $cr)
                                            <option value="{{ $cr->id }}">{{ $cr->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <input type="hidden" name="student_id" :value="selected?.id || ''">
                            <x-input-error class="mt-2" :messages="$errors->get('student_id')" />
                            <p class="mt-1 text-xs text-gray-600">Search and select a student to filter categories by class.</p>

                            <div x-show="selected" x-cloak class="mt-3 rounded-md border p-3 text-sm">
                                <div class="font-semibold text-gray-800">Selected Student</div>
                                <div class="mt-1 text-gray-700">Name: <span x-text="selected?.name"></span></div>
                                <div class="mt-1 text-gray-700">Class: <span x-text="selected?.class"></span></div>
                                <template x-if="selected?.monthly_fee !== undefined">
                                    <div class="mt-1 text-gray-700">Monthly Fee: <span x-text="Number(selected?.monthly_fee||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})"></span></div>
                                </template>
                                <template x-if="selected?.due_amount !== undefined">
                                    <div class="mt-1 text-gray-700">Due Amount: <span x-text="Number(selected?.due_amount||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})"></span></div>
                                </template>
                                <template x-if="selected?.monthly_category_id">
                                    <div class="mt-2 p-2 rounded flex items-center justify-between" :class="isMonthlySelected() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'">
                                        <span x-text="isMonthlySelected() ? 'You selected the Monthly Fee category.' : 'To reduce monthly due, select the Monthly Fee category.'"></span>
                                        <div class="flex items-center gap-2">
                                            <template x-if="!isMonthlySelected()">
                                                <button type="button" class="rounded-md px-3 py-1.5 text-xs font-semibold text-white shadow bg-amber-600 hover:bg-amber-700" x-on:click="setMonthlyCategory()">Change Category</button>
                                            </template>
                                            <template x-if="isMonthlySelected()">
                                                <span class="rounded-md px-3 py-1.5 text-xs font-semibold text-white shadow bg-emerald-600">Monthly Selected ✓</span>
                                            </template>
                                            <button type="button" class="rounded-md px-3 py-1.5 text-xs font-semibold text-white shadow bg-emerald-600 hover:bg-emerald-700" x-on:click="fillMonthlyAmount()">Fill Amount</button>
                                        </div>
                                    </div>
                                </template>
                                <div class="mt-2">
                                    <button type="button" class="text-xs text-gray-600 hover:underline" x-on:click="clearSelection()">Clear</button>
                                </div>
                            </div>

                            
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="amount" :value="__('Amount')" />
                                <x-text-input id="amount" name="amount" type="number" step="0.01" class="mt-1 block w-full" :value="old('amount')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                            </div>
                            <div>
                                <x-input-label for="paid_at" :value="__('Date')" />
                                <x-text-input id="paid_at" name="paid_at" type="text" placeholder="DD-MM-YYYY" class="mt-1 block w-full" :value="old('paid_at', now()->format('d-m-Y'))" required />
                                <x-input-error class="mt-2" :messages="$errors->get('paid_at')" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="bill_no" :value="__('Bill Number (optional)')" />
                            <x-text-input id="bill_no" name="bill_no" type="text" class="mt-1 block w-full" :value="old('bill_no')" />
                            <p class="mt-1 text-xs text-gray-600">Type a bill number to override. Leave blank to auto-generate if enabled in Settings.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('bill_no')" />
                        </div>

                        <div>
                            <x-input-label for="notes" :value="__('Notes (optional)')" />
                            <textarea id="notes" name="notes" class="mt-1 block w-full rounded-md border-gray-300" rows="3">{{ old('notes') }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Save</x-primary-button>
                            <a href="{{ route('revenue.items.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                        </div>
                    </form>

                    <script>
                        // Category meta for quick client-side type lookup
                        const CATEGORY_META = @json($categories->mapWithKeys(fn($c)=>[$c->id=>['payment_type'=>$c->payment_type]]) );

                        function studentPicker() {
                            return {
                                q: '',
                                classRoomId: '',
                                results: [],
                                open: false,
                                selected: null,
                                currentCategoryId: null,
                                initialStudentId: null,
                                stripStudentParam(){
                                    try{
                                        const u = new URL(window.location.href);
                                        if(u.searchParams.has('student_id')){
                                            u.searchParams.delete('student_id');
                                            const qs = u.searchParams.toString();
                                            window.history.replaceState({}, '', u.pathname + (qs ? ('?'+qs) : ''));
                                        }
                                    }catch(e){}
                                },
                                highlightedIndex: -1,
                                init() {
                                    // Get the initial student ID from data attribute
                                    const val = this.$el.getAttribute('data-student-id');
                                    if (val) {
                                        this.initialStudentId = val;
                                        this.loadInitialStudent();
                                        // Remove student_id param so refresh starts clean
                                        this.stripStudentParam();
                                    }
                                    const sel = document.getElementById('revenue_category_id');
                                    if (sel) {
                                        this.currentCategoryId = sel.value;
                                        sel.addEventListener('change', () => { this.currentCategoryId = sel.value; });
                                    }
                                    const input = document.getElementById('student_search');
                                    if (input) {
                                        input.addEventListener('keydown', (e) => this.handleKeyDown(e));
                                    }
                                    document.addEventListener('click', (e) => {
                                        const box = document.getElementById('student_search');
                                        if (!box) return;
                                        const within = box.parentElement.contains(e.target);
                                        this.open = within && (this.results?.length || 0) > 0;
                                    });
                                },
                                handleKeyDown(e) {
                                    if (!this.open || this.results.length === 0) {
                                        if (e.key === 'Escape') this.open = false;
                                        return;
                                    }
                                    if (e.key === 'ArrowDown') {
                                        e.preventDefault();
                                        this.highlightedIndex = (this.highlightedIndex + 1) % this.results.length;
                                    } else if (e.key === 'ArrowUp') {
                                        e.preventDefault();
                                        this.highlightedIndex = this.highlightedIndex <= 0 ? this.results.length - 1 : this.highlightedIndex - 1;
                                    } else if (e.key === 'Enter') {
                                        e.preventDefault();
                                        if (this.highlightedIndex >= 0 && this.results[this.highlightedIndex]) {
                                            this.select(this.results[this.highlightedIndex]);
                                        }
                                    } else if (e.key === 'Escape') {
                                        e.preventDefault();
                                        this.open = false;
                                        this.highlightedIndex = -1;
                                    }
                                },
                                async search() {
                                    const hasQuery = (this.q && this.q.trim().length > 0);
                                    if (!hasQuery) { this.results = []; this.open = false; this.highlightedIndex = -1; return; }
                                    const params = new URLSearchParams();
                                    params.set('q', this.q.trim());
                                    if (this.classRoomId) params.set('class_room_id', this.classRoomId);
                                    params.set('limit', '10');
                                    const res = await fetch('/students/search?'+params.toString(), { headers: { 'Accept': 'application/json' } });
                                    const data = await res.json();
                                    this.results = data.results || [];
                                    this.open = this.results.length > 0;
                                    this.highlightedIndex = -1;
                                },
                                async openDefault(){
                                    // On click of search icon: if empty query, show 5 items max (optionally by class)
                                    const params = new URLSearchParams();
                                    if (this.q && this.q.trim()) params.set('q', this.q.trim());
                                    if (this.classRoomId) params.set('class_room_id', this.classRoomId);
                                    params.set('limit', (this.q && this.q.trim()) ? '10' : '5');
                                    const res = await fetch('/students/search?'+params.toString(), { headers: { 'Accept': 'application/json' } });
                                    const data = await res.json();
                                    this.results = data.results || [];
                                    this.open = this.results.length > 0;
                                    this.highlightedIndex = -1;
                                },
                                async select(item) { this.selected = item; this.open = false; this.highlightedIndex = -1; await this.fetchDetails(item?.id); },
                                clearSelection() { this.selected = null; this.stripStudentParam(); },
                                async fetchDetails(id){
                                    if(!id) return;
                                    try{
                                        const r = await fetch(`/students/search?id=${id}`, { headers: { 'Accept':'application/json' } });
                                        const d = await r.json();
                                        if(d.results && d.results.length){ this.selected = d.results[0]; }
                                    }catch(e){}
                                },
                                loadInitialStudent() {
                                    const studentId = this.initialStudentId;
                                    if (!studentId) return;
                                    fetch(`/students/search?id=${studentId}`, { headers: { 'Accept': 'application/json' } })
                                        .then(r => r.json())
                                        .then(data => {
                                            if (data.results && data.results.length) {
                                                this.selected = data.results[0];
                                            }
                                        })
                                        .catch(() => {});
                                },
                                isMonthlySelected(){
                                    const id = this.currentCategoryId;
                                    return !!(this.selected?.monthly_category_id && id && String(id) === String(this.selected.monthly_category_id));
                                },
                                setMonthlyCategory(){
                                    const sel = document.getElementById('revenue_category_id');
                                    if(sel && this.selected?.monthly_category_id){ sel.value = this.selected.monthly_category_id; sel.dispatchEvent(new Event('change', {bubbles:true})); }
                                },
                                fillMonthlyAmount(){
                                    const a = document.getElementById('amount');
                                    if(a && this.selected?.monthly_fee){ a.value = this.selected.monthly_fee; a.focus(); }
                                }
                            };
                        }

                        // Monthly Fee helpers
                        function selectMonthlyCategory() {
                            var s=document.getElementById('revenue_category_id');
                            if(!s) return;
                            for (var i=0;i<s.options.length;i++) {
                                if (s.options[i].value==='{{ $monthlyCatId ?? '' }}') { s.selectedIndex=i; break; }
                            }
                            refreshMonthlyNotice();
                        }

                        function updateCategoryTypeIndicator() {
                            const sel=document.getElementById('revenue_category_id');
                            const badge=document.getElementById('categoryTypeInfo');
                            if(!sel || !badge) return;
                            const id=sel.value;
                            const meta=CATEGORY_META?.[id];
                            const type=(meta?.payment_type||'').toString();
                            if(!type){ badge.textContent=''; badge.className='inline-flex items-center rounded px-2 py-0.5 font-medium'; return; }
                            const labelMap={
                                monthly: {text:'Type: Monthly', cls:'bg-emerald-50 text-emerald-700 border border-emerald-200'},
                                '2_months': {text:'Type: Every 2 Months', cls:'bg-sky-50 text-sky-700 border border-sky-200'},
                                '3_months': {text:'Type: Every 3 Months', cls:'bg-sky-50 text-sky-700 border border-sky-200'},
                                '6_months': {text:'Type: Every 6 Months', cls:'bg-indigo-50 text-indigo-700 border border-indigo-200'},
                                yearly: {text:'Type: Yearly', cls:'bg-indigo-50 text-indigo-700 border border-indigo-200'},
                                'one_time': {text:'Type: One-time', cls:'bg-gray-50 text-gray-700 border border-gray-200'},
                            };
                            const map=labelMap[type] || {text:`Type: ${type}`, cls:'bg-gray-50 text-gray-700 border border-gray-200'};
                            badge.textContent=map.text;
                            badge.className='inline-flex items-center rounded px-2 py-0.5 font-medium '+map.cls;
                        }

                        function applyMonthlyFeeFill() {
                            selectMonthlyCategory();
                            var amount=document.getElementById('amount');
                            if(amount){ amount.value = '{{ $selectedStudent?->monthly_fee ?? '' }}'; amount.focus(); }
                        }

                        function refreshMonthlyNotice() {
                            var s=document.getElementById('revenue_category_id');
                            var box=document.getElementById('monthlyNotice');
                            var text=document.getElementById('monthlyNoticeText');
                            var btn=document.getElementById('btnSelectMonthly');
                            if(!s || !box || !text || !btn) return;
                            var isMonthly = s.value === '{{ $monthlyCatId ?? '' }}';
                            if(isMonthly) {
                                box.className = 'mt-2 p-2 rounded bg-emerald-50 text-emerald-700 flex items-center justify-between';
                                text.innerHTML = 'You selected the <strong>Monthly Fee</strong> category.';
                                btn.textContent = 'Monthly Selected ✓';
                                btn.setAttribute('disabled','disabled');
                                btn.className = 'rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow opacity-90';
                            } else {
                                box.className = 'mt-2 p-2 rounded bg-amber-50 text-amber-700 flex items-center justify-between';
                                text.innerHTML = 'To reduce monthly due, select the <strong>Monthly Fee</strong> category.';
                                btn.textContent = 'Select Monthly Fee';
                                btn.removeAttribute('disabled');
                                btn.className = 'rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white shadow hover:bg-amber-700';
                            }
                        }

                        // Quick Monthly button in header
                        document.addEventListener('DOMContentLoaded', () => {
                            const btn=document.getElementById('quickMonthlyBtn');
                            if(btn){ btn.addEventListener('click', () => applyMonthlyFeeFill()); }
                            const select=document.getElementById('revenue_category_id');
                            if(select){ select.addEventListener('change', () => { refreshMonthlyNotice(); updateCategoryTypeIndicator(); }); refreshMonthlyNotice(); updateCategoryTypeIndicator(); }
                            // If opened via quick=monthly, preselect category and focus amount
                            const params=new URLSearchParams(window.location.search);
                            if(params.get('quick')==='monthly') { selectMonthlyCategory(); const a=document.getElementById('amount'); if(a){ a.focus(); } }
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
