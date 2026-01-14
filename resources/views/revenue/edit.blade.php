<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Revenue</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('revenue.items.update', $item) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="revenue_category_id" :value="__('Category')" />
                            <select id="revenue_category_id" name="revenue_category_id" class="mt-1 block w-full rounded-md border-gray-300">
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('revenue_category_id', $item->revenue_category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('revenue_category_id')" />
                        </div>

                        <div x-data="studentPickerEdit()" x-init="init({{ json_encode($item->student_id) }})">
                            <x-input-label for="student_search" :value="__('Student (optional)')" />
                            <div class="mt-1 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                <div class="sm:col-span-2">
                                    <div class="relative">
                                        <input id="student_search" type="text" class="block w-full rounded-md border-gray-300 pr-10" placeholder="Search by admission no, name, phone" x-model="q" x-on:input.debounce.300ms="search()">
                                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.3-4.3"/></svg>
                                        </span>
                                        <div x-show="open" x-cloak class="absolute z-10 mt-1 w-full rounded-md border bg-white shadow">
                                            <template x-if="results.length === 0">
                                                <div class="px-3 py-2 text-sm text-gray-600">No matches.</div>
                                            </template>
                                            <template x-for="item in results" :key="item.id">
                                                <button type="button" class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-gray-50" x-on:click="select(item)">
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

                            <div x-show="selected" x-cloak class="mt-3 rounded-md border p-3 text-sm">
                                <div class="font-semibold text-gray-800">Selected Student</div>
                                <div class="mt-1 text-gray-700">Name: <span x-text="selected?.name"></span></div>
                                <div class="mt-1 text-gray-700">Class: <span x-text="selected?.class"></span></div>
                                <div class="mt-2">
                                    <button type="button" class="text-xs text-gray-600 hover:underline" x-on:click="clearSelection()">Clear</button>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="amount" :value="__('Amount')" />
                                <x-text-input id="amount" name="amount" type="number" step="0.01" class="mt-1 block w-full" :value="old('amount', $item->amount)" required />
                                <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                            </div>
                            <div>
                                <x-input-label for="paid_at" :value="__('Date')" />
                                <x-text-input id="paid_at" name="paid_at" type="text" placeholder="DD-MM-YYYY" class="mt-1 block w-full" :value="old('paid_at', optional($item->paid_at)->format('d-m-Y'))" required />
                                <x-input-error class="mt-2" :messages="$errors->get('paid_at')" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="bill_no" :value="__('Bill Number')" />
                            <x-text-input id="bill_no" name="bill_no" type="text" class="mt-1 block w-full" :value="old('bill_no', $item->bill_no)" />
                            <x-input-error class="mt-2" :messages="$errors->get('bill_no')" />
                        </div>

                        <div>
                            <x-input-label for="notes" :value="__('Notes (optional)')" />
                            <textarea id="notes" name="notes" class="mt-1 block w-full rounded-md border-gray-300" rows="3">{{ old('notes', $item->notes) }}</textarea>
                            <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Save</x-primary-button>
                            <a href="{{ route('revenue.items.index') }}" class="text-sm text-gray-600 hover:underline">Back</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    function studentPickerEdit() {
        return {
            q: '',
            classRoomId: '',
            results: [],
            open: false,
            selected: null,
            init(initialId) {
                // If editing an existing item, hydrate selection from the server list
                if (initialId) {
                    // best-effort fetch to populate selected student summary
                    fetch('/students/search?q=&class_room_id=').then(r => r.json()).then(d => {
                        // not reliable for a specific id; leave summary empty until user searches
                    });
                }
                this.search();
                document.addEventListener('click', (e) => {
                    const box = document.getElementById('student_search');
                    if (!box) return;
                    const within = box.parentElement.contains(e.target);
                    this.open = within && (this.results?.length || 0) > 0;
                });
            },
            async search() {
                const params = new URLSearchParams();
                if (this.q) params.set('q', this.q);
                if (this.classRoomId) params.set('class_room_id', this.classRoomId);
                const res = await fetch('/students/search?'+params.toString(), { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                this.results = data.results || [];
                this.open = this.results.length > 0;
            },
            select(item) { this.selected = item; this.open = false; },
            clearSelection() { this.selected = null; },
        };
    }
</script>
