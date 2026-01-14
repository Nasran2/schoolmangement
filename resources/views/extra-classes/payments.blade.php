<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-bold text-gray-800">Class Payments</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 capitalize">{{ $extraClass->payment_type }}</span>
            </div>
            <a href="{{ route('extra-classes.show', $extraClass) }}" class="inline-flex items-center px-3 py-2 rounded-md border border-gray-300 text-gray-700">Back</a>
        </div>
    </x-slot>

    <form action="{{ route('extra-classes.payments.update', $extraClass) }}" method="POST" class="py-6">
        @csrf
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Class:</div>
                    <div class="text-lg font-bold text-gray-900">{{ $extraClass->name }}</div>
                    <div class="text-xs text-gray-500">Fee per student: {{ number_format($extraClass->fee, 2) }}</div>
                </div>
                <div class="text-xs text-gray-500">
                    {{ now()->format('d/m/Y H:i:s') }}
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Paid</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($enrollments as $index => $row)
                            <tr class="hover:bg-gray-50/80 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-9 w-9 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center font-bold text-sm">
                                            {{ substr($row->student?->name ?? '?', 0, 1) }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $row->student?->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $row->student?->admission_number }}</div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $row->id }}">
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-center" x-data="{ paid: {{ $row->paid ? 'true' : 'false' }} }">
                                    <input type="hidden" name="items[{{ $index }}][paid]" :value="paid ? '1' : '0'">
                                    <button type="button"
                                            @click="paid = !paid"
                                            :class="paid ? 'bg-indigo-600' : 'bg-gray-200'"
                                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2">
                                        <span class="sr-only">Toggle Payment</span>
                                        <span aria-hidden="true"
                                              :class="paid ? 'translate-x-5' : 'translate-x-0'"
                                              class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                                    </button>
                                    <div class="mt-1 text-[10px] font-medium uppercase tracking-wide" :class="paid ? 'text-indigo-600' : 'text-gray-400'" x-text="paid ? 'Paid' : 'Unpaid'"></div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-700 font-medium font-mono">
                                    {{ number_format($row->amount ?? $extraClass->fee, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <div class="text-sm text-gray-500">
                    Showing {{ $enrollments->firstItem() ?? 0 }} to {{ $enrollments->lastItem() ?? 0 }} of {{ $enrollments->total() }} students
                </div>
                <div class="flex items-center gap-4">
                     {{ $enrollments->links() }}
                     <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all transform active:scale-95">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </form>
</x-app-layout>
