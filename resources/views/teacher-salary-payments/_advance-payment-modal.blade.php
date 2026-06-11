<div id="advance-payment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-40 px-4">
    <div class="w-full max-w-lg rounded-lg bg-white shadow-xl">
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Advance Salary Payment</h3>
                <button type="button" onclick="closeAdvancePaymentModal()" class="rounded-md p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700" aria-label="Close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('teacher-salary-payments.advance.store') }}" class="space-y-5 px-6 py-5">
            @csrf

            <div>
                <x-input-label for="advance_paid_at" :value="__('Paid Date *')" class="mb-2 font-semibold" />
                <x-text-input id="advance_paid_at" name="paid_at" type="text" data-datepicker class="block w-full" :value="now()->toDateString()" required />
            </div>

            <div>
                <x-input-label for="advance_teacher_id" :value="__('Teacher *')" class="mb-2 font-semibold" />
                <select id="advance_teacher_id" name="teacher_id" data-searchable-select data-placeholder="Search teacher by name or phone" required class="block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Select teacher</option>
                    @foreach($teachers as $teacher)
                        <option value="{{ $teacher->id }}">{{ $teacher->name }}{{ $teacher->phone ? ' - '.$teacher->phone : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label for="advance_amount" :value="__('Amount (Rs) *')" class="mb-2 font-semibold" />
                <div class="relative">
                    <span class="absolute left-3 top-3 text-sm font-medium text-gray-500">Rs</span>
                    <x-text-input id="advance_amount" name="amount" type="number" step="0.01" min="0.01" class="block w-full pl-10" required />
                </div>
            </div>

            <div>
                <x-input-label for="advance_notes" :value="__('Notes')" class="mb-2 font-semibold" />
                <textarea id="advance_notes" name="notes" rows="3" class="block w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500" placeholder="Optional note"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-200 pt-5">
                <button type="button" onclick="closeAdvancePaymentModal()" class="rounded-lg bg-gray-200 px-4 py-2 font-medium text-gray-800 hover:bg-gray-300">Cancel</button>
                <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 font-semibold text-white hover:bg-amber-700">Save Advance</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAdvancePaymentModal(teacherId = null) {
        const modal = document.getElementById('advance-payment-modal');
        const select = document.getElementById('advance_teacher_id');
        if (select && teacherId) {
            if (select.tomselect) {
                select.tomselect.setValue(String(teacherId), true);
            } else {
                select.value = String(teacherId);
            }
        }
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    function closeAdvancePaymentModal() {
        const modal = document.getElementById('advance-payment-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }
</script>
