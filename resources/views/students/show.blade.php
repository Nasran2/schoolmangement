<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Student Details</h2>
                <p class="text-gray-600 text-sm mt-1">View student information, payments and monthly tracker</p>
            </div>
            <div class="flex flex-wrap gap-2 justify-end">
                @can('students.manage')
                    <a href="{{ route('students.edit', $student) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg shadow hover:bg-blue-700 transition-all">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </a>
                @endcan

                @can('students.promote')
                    <span x-data="{ open: false, reason: '' }" class="inline">
                        <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-lg shadow hover:bg-emerald-700 transition-all" x-on:click="open=true">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" />
                            </svg>
                            Promote
                        </button>
                        <form x-ref="promoteForm" class="hidden" method="POST" action="{{ route('students.promote.one', $student) }}">
                            @csrf
                            <input type="hidden" name="reason" x-model="reason">
                        </form>
                        <template x-teleport="body">
                            <div x-cloak x-show="open">
                                <div class="fixed inset-0 bg-black/40 z-[100]" x-on:click="open=false"></div>
                                <div class="fixed inset-0 z-[101] flex items-center justify-center p-4">
                                    <div class="w-[90%] max-w-md rounded-lg bg-white p-5 shadow-xl">
                                        <div class="text-base font-semibold text-gray-800">Promote Student</div>
                                        <div class="mt-2 text-sm text-gray-600">Promote {{ $student->name }} to the next class?</div>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700">Reason (optional)</label>
                                            <textarea x-model="reason" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" rows="3" placeholder="Add a note or reason for this promotion..."></textarea>
                                        </div>
                                        <div class="mt-5 flex justify-end gap-2">
                                            <button type="button" class="rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" x-on:click="open=false; reason=''">Cancel</button>
                                            <button type="button" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" x-on:click="$refs.promoteForm.submit()">Promote</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </span>
                @endcan

                @can('students.demote')
                    <span x-data="{ open: false, reason: '' }" class="inline">
                        <button type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-rose-600 text-white text-sm font-semibold rounded-lg shadow hover:bg-rose-700 transition-all" x-on:click="open=true">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                            </svg>
                            Demote
                        </button>
                        <form x-ref="demoteForm" class="hidden" method="POST" action="{{ route('students.demote.one', $student) }}">
                            @csrf
                            <input type="hidden" name="reason" x-model="reason">
                        </form>
                        <template x-teleport="body">
                            <div x-cloak x-show="open">
                                <div class="fixed inset-0 bg-black/40 z-[100]" x-on:click="open=false"></div>
                                <div class="fixed inset-0 z-[101] flex items-center justify-center p-4">
                                    <div class="w-[90%] max-w-md rounded-lg bg-white p-5 shadow-xl">
                                        <div class="text-base font-semibold text-gray-800">Demote Student</div>
                                        <div class="mt-2 text-sm text-gray-600">Demote {{ $student->name }} to the previous class?</div>
                                        <div class="mt-4">
                                            <label class="block text-sm font-medium text-gray-700">Reason (optional)</label>
                                            <textarea x-model="reason" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-rose-500 focus:ring-rose-500" rows="3" placeholder="Add a note or reason for this demotion..."></textarea>
                                        </div>
                                        <div class="mt-5 flex justify-end gap-2">
                                            <button type="button" class="rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" x-on:click="open=false; reason=''">Cancel</button>
                                            <button type="button" class="rounded-md bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700" x-on:click="$refs.demoteForm.submit()">Demote</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </span>
                @endcan

                @can('students.manage')
                    <a href="{{ route('students.statement', $student) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-800 text-sm font-semibold rounded-lg border border-gray-200 hover:bg-gray-50 transition-all">
                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 2h9l5 5v15a2 2 0 01-2 2H6a2 2 0 01-2-2V4a2 2 0 012-2z" />
                        </svg>
                        Statement (PDF)
                    </a>

                    <a href="{{ route('students.admission', $student) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-800 text-sm font-semibold rounded-lg border border-gray-200 hover:bg-gray-50 transition-all">
                        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m-8 4h10a2 2 0 002-2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Admission (PDF)
                    </a>
                @endcan

                <a href="{{ route('students.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-200 transition-all">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-lg bg-green-50 border border-green-200 p-4 shadow-sm">
                    <div class="flex items-center gap-3">
                        <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm font-medium text-green-800">{{ session('status') }}</span>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Student Profile Card -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-6 text-center">
                            <div class="bg-white/20 backdrop-blur-sm rounded-full p-4 w-20 h-20 mx-auto mb-3 flex items-center justify-center text-white text-3xl font-bold">
                                {{ strtoupper(mb_substr($student->name ?? 'S', 0, 1)) }}
                            </div>
                            <h3 class="text-xl font-bold text-white">{{ $student->name }}</h3>
                            <span class="inline-block mt-2 px-3 py-1 text-xs font-semibold text-white bg-white/20 backdrop-blur-sm rounded-full">
                                {{ $student->active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="p-6 space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="bg-indigo-100 rounded-lg p-2">
                                    <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs font-semibold text-gray-500 uppercase">Admission No</div>
                                    <div class="text-sm text-gray-900 mt-1">{{ $student->admission_number ?? '-' }}</div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="bg-blue-100 rounded-lg p-2">
                                    <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs font-semibold text-gray-500 uppercase">Class</div>
                                    <div class="text-sm text-gray-900 mt-1">{{ $student->classRoom?->name ?? ($student->class ?? '-') }}</div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="bg-green-100 rounded-lg p-2">
                                    <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs font-semibold text-gray-500 uppercase">Phone</div>
                                    <div class="text-sm text-gray-900 mt-1">{{ $student->phone ?? 'Not provided' }}</div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="bg-yellow-100 rounded-lg p-2">
                                    <svg class="h-5 w-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs font-semibold text-gray-500 uppercase">Joining Date</div>
                                    <div class="text-sm text-gray-900 mt-1">{{ optional($student->joining_date)->format('M d, Y') ?? 'Not specified' }}</div>
                                </div>
                            </div>

                            <div class="mt-6 pt-6 border-t border-gray-200 space-y-4">
                                <div class="text-sm font-semibold text-gray-900">Guardian / Parents</div>
                                @if($student->use_guardian)
                                    <div class="rounded-xl border border-orange-200 bg-orange-50 p-4">
                                        <div class="text-xs font-semibold text-orange-700 uppercase">Guardian</div>
                                        <div class="mt-2 space-y-1 text-sm text-gray-900">
                                            <div><span class="text-gray-500">Name:</span> {{ $student->guardian_name ?? '-' }}</div>
                                            <div><span class="text-gray-500">Relationship:</span> {{ $student->guardian_relationship ?? '-' }}</div>
                                            <div><span class="text-gray-500">Phone:</span> {{ $student->guardian_phone ?? '-' }}</div>
                                        </div>
                                    </div>
                                @endif

                                <div class="grid grid-cols-1 gap-3">
                                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                        <div class="text-xs font-semibold text-gray-600 uppercase">Father</div>
                                        <div class="mt-1 text-sm text-gray-900">{{ $student->father_name_with_initial ?? '-' }}</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                        <div class="text-xs font-semibold text-gray-600 uppercase">Mother</div>
                                        <div class="mt-1 text-sm text-gray-900">{{ $student->mother_name_with_initial ?? '-' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payments + Monthly Tracker -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Account Summary -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Account Summary</h3>
                                <p class="text-sm text-gray-600 mt-1">Monthly fee progress and due amount</p>
                            </div>
                            @can('revenue.add')
                                <a href="{{ route('revenue.items.create', ['student_id' => $student->id, 'quick' => 'monthly']) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white text-sm font-semibold rounded-lg shadow hover:from-green-700 hover:to-emerald-700 transition-all">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Quick Monthly Payment
                                </a>
                            @endcan
                        </div>

                        <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <div class="text-xs font-semibold text-gray-500 uppercase">Monthly Fee</div>
                                <div class="mt-1 text-2xl font-bold text-gray-900">Rs {{ number_format((float)($dueBreakdown['monthlyFee'] ?? 0), 2) }}</div>
                                <div class="mt-1 text-xs text-gray-500">Start: {{ $dueBreakdown['startDate'] ? \Carbon\Carbon::parse($dueBreakdown['startDate'])->format('Y-m-d') : '-' }}</div>
                            </div>
                            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
                                <div class="text-xs font-semibold text-blue-700 uppercase">Expected</div>
                                <div class="mt-1 text-2xl font-bold text-blue-700">Rs {{ number_format((float)($dueBreakdown['expectedDue'] ?? 0), 2) }}</div>
                                <div class="mt-1 text-xs text-blue-700/70">Months: {{ (int)($dueBreakdown['monthsDue'] ?? 0) }}</div>
                            </div>
                            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
                                <div class="text-xs font-semibold text-rose-700 uppercase">Balance</div>
                                <div class="mt-1 text-2xl font-bold text-rose-700">Rs {{ number_format((float)($dueBreakdown['netDue'] ?? 0), 2) }}</div>
                                <div class="mt-1 text-xs text-rose-700/70">Paid: Rs {{ number_format((float)($dueBreakdown['paidMonthlyFee'] ?? 0), 2) }}</div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <div class="flex items-center justify-between text-sm">
                                <div class="font-semibold text-gray-900">Paid Months</div>
                                <div class="text-gray-600">{{ (int)($progress['monthsPaidCount'] ?? 0) }} / {{ (int)($progress['monthsDueCount'] ?? 0) }} ({{ (int)($progress['paidPct'] ?? 0) }}%)</div>
                            </div>
                            <div class="mt-2 h-2 rounded-full bg-gray-100 overflow-hidden">
                                <div class="h-2 bg-emerald-500" style="width: {{ (int)($progress['paidPct'] ?? 0) }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Payment Tracker -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Paid Month Tracker</h3>
                                <p class="text-sm text-gray-600 mt-1">Shows paid/unpaid months based on monthly fee</p>
                            </div>
                        </div>

                        @php
                            $monthlyFeeValue = (float) ($student->monthly_fee ?? 0);
                            $feeStartDateValue = $student->fee_start_date;
                            $canShowTracker = !empty($feeStartDateValue) && $monthlyFeeValue > 0;

                            $cycles = [];
                            $paidCyclesCount = 0;
                            $allocations = collect();

                            if ($canShowTracker) {
                                // Use MonthlyFeeAllocator to get accurate ledger
                                $allocator = app(\App\Services\Billing\MonthlyFeeAllocator::class);
                                $ledger = $allocator->buildLedger($student, 12);
                                
                                // Convert ledger to cycles format for display
                                $cycles = [];
                                $now = now();
                                foreach ($ledger as $key => $data) {
                                    $s = \Carbon\Carbon::createFromDate($data['year'], $data['month'], 1)->startOfMonth();
                                    $e = $s->copy()->endOfMonth();
                                    $cycles[] = [
                                        'start' => $s,
                                        'end' => $e,
                                        'inProgress' => $now->betweenIncluded($s, $e),
                                        'isFuture' => $s->gt($now->endOfMonth()),
                                        'month' => $data['month'],
                                        'year' => $data['year'],
                                        'status' => $data['status'], // 'paid', 'partially_paid', 'unpaid'
                                        'remaining' => $data['remaining'],
                                    ];
                                }

                                // Slice logic: Show last 12 months relative to NOW, plus all future months
                                // Find index of "current" month
                                $currentIndex = -1;
                                foreach ($cycles as $idx => $c) {
                                    if ($c['inProgress']) {
                                        $currentIndex = $idx;
                                        break;
                                    }
                                }
                                // If no current month (e.g. start date in future), current is effectively 0 or -1
                                if ($currentIndex === -1) {
                                    $start = \Carbon\Carbon::parse($feeStartDateValue)->startOfMonth();
                                    if ($start->gt($now)) $currentIndex = 0; // Start is future
                                    else $currentIndex = count($cycles) - 1; // End is past
                                }

                                $sliceStart = max(0, $currentIndex - 11);
                                $cycles = array_slice($cycles, $sliceStart);
                            }
                        @endphp

                        @if(!$canShowTracker)
                            <div class="text-sm text-gray-600">Set Fee Start Date and Monthly Fee to see the tracker.</div>
                        @else
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                @foreach($cycles as $i => $cy)
                                    @php
                                        $status = 'unpaid';
                                        if ($cy['status'] === 'paid') {
                                            $status = $cy['isFuture'] ? 'advance' : 'paid';
                                        } elseif ($cy['status'] === 'partially_paid') {
                                            $status = 'partial';
                                        } elseif ($cy['inProgress']) {
                                            $status = 'current';
                                        }

                                        $label = $cy['start']->format('M Y');
                                    @endphp
                                    <div class="relative group">
                                        @if($status === 'advance')
                                            <div class="border-2 border-indigo-500 bg-indigo-50 rounded-lg p-3 text-center hover:shadow-md transition-all">
                                                <div class="text-xs font-semibold text-indigo-700 mb-1">{{ $label }}</div>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                                </svg>
                                                <div class="text-xs font-bold text-indigo-700 mt-1">Advance</div>
                                            </div>
                                        @elseif($status === 'partial')
                                            <div class="border-2 border-orange-400 bg-orange-50 rounded-lg p-3 text-center hover:shadow-md transition-all">
                                                <div class="text-xs font-semibold text-orange-700 mb-1">{{ $label }}</div>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 019 9v.375M10.125 2.25A3.375 3.375 0 0113.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 013.375 3.375M9 15l2.25 2.25L15 12" />
                                                </svg>
                                                <div class="text-xs font-bold text-orange-700 mt-1">Partial</div>
                                                <div class="text-[10px] font-medium text-orange-800 mt-0.5">Bal: {{ number_format($cy['remaining']) }}</div>
                                            </div>
                                        @elseif($status === 'paid')
                                            <div class="border-2 border-green-500 bg-green-50 rounded-lg p-3 text-center hover:shadow-md transition-all">
                                                <div class="text-xs font-semibold text-green-700 mb-1">{{ $label }}</div>
                                                <svg class="h-6 w-6 text-green-600 mx-auto" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                </svg>
                                                <div class="text-xs font-bold text-green-700 mt-1">Paid</div>
                                            </div>
                                        @elseif($status === 'current')
                                            <div class="border-2 border-amber-400 bg-amber-50 rounded-lg p-3 text-center hover:shadow-md transition-all">
                                                <div class="text-xs font-semibold text-amber-700 mb-1">{{ $label }}</div>
                                                <svg class="h-6 w-6 text-amber-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3" />
                                                </svg>
                                                <div class="text-xs font-semibold text-amber-700 mt-1">Current</div>
                                            </div>
                                        @else
                                            <div class="border-2 border-gray-200 bg-gray-50 rounded-lg p-3 text-center hover:shadow-md transition-all">
                                                <div class="text-xs font-semibold text-gray-500 mb-1">{{ $label }}</div>
                                                <svg class="h-6 w-6 text-gray-400 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                <div class="text-xs font-semibold text-gray-500 mt-1">Unpaid</div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Payment History -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                        <div class="flex items-center justify-between gap-4 mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Payment History</h3>
                                <p class="text-sm text-gray-600 mt-1">Filter and view student revenue payments</p>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ request()->fullUrlWithQuery(['download' => 1]) }}" class="inline-flex items-center gap-2 px-3 py-2 bg-white text-gray-700 text-sm font-semibold rounded-lg border border-gray-200 hover:bg-gray-50">
                                    <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l4-4m-4 4l-4-4m8 6H8" />
                                    </svg>
                                    Download CSV
                                </a>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('students.show', $student) }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-5">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase">From</label>
                                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase">To</label>
                                <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase">Type</label>
                                <select name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="" {{ empty($filters['type']) ? 'selected' : '' }}>All</option>
                                    <option value="monthly" {{ ($filters['type'] ?? '') === 'monthly' ? 'selected' : '' }}>Monthly Fee</option>
                                    <option value="other" {{ ($filters['type'] ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>
                            <div class="flex items-end gap-2">
                                <button type="submit" class="inline-flex items-center justify-center gap-2 w-full px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg shadow hover:bg-blue-700">
                                    Filter
                                </button>
                            </div>
                        </form>

                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Bill</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Category</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Amount</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse ($payments as $p)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $p->bill_no ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ optional($p->paid_at)->format('Y-m-d') }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $p->category?->name ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">{{ number_format((float) $p->amount, 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-right">
                                                <div class="inline-flex items-center gap-3 text-gray-500">
                                                    <a href="{{ route('revenue.items.receipt', $p) }}" class="hover:text-indigo-600" title="Print / View Receipt">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                                                        </svg>
                                                    </a>
                                                    @can('revenue.manage')
                                                        <a href="{{ route('revenue.items.edit', $p) }}" class="hover:text-indigo-600" title="Edit">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A1.25 1.25 0 0116.75 20H5.25A1.25 1.25 0 014 18.75V7.25A1.25 1.25 0 015.25 6H10" />
                                                            </svg>
                                                        </a>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="px-4 py-6 text-center text-sm text-gray-600" colspan="5">No payments found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">{{ $payments->links() }}</div>
                    </div>

                    <!-- Promotion History -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">Promotion / Demotion History</h3>

                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Action</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">From</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">To</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">By</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse($history as $h)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ optional($h->created_at)->format('Y-m-d') }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $h->action === 'promote' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                                    {{ $h->action === 'promote' ? 'Promoted' : 'Demoted' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $h->fromClassRoom?->name ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $h->toClassRoom?->name ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $h->performer?->name ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="px-4 py-6 text-center text-sm text-gray-600" colspan="5">No promotion history.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">{{ $history->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
