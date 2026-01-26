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
                            @php $badge = $student->alumni ? 'Alumni' : ($student->active ? 'Active' : 'Inactive'); @endphp
                            <span class="inline-block mt-2 px-3 py-1 text-xs font-semibold text-white bg-white/20 backdrop-blur-sm rounded-full">
                                {{ $badge }}
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
                                    <div class="text-sm text-gray-900 mt-1">{{ $student->alumni ? 'Alumni' : ($student->classRoom?->name ?? ($student->class ?? '-')) }}</div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <div class="bg-rose-100 rounded-lg p-2">
                                    <svg class="h-5 w-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs font-semibold text-gray-500 uppercase">Due Amount</div>
                                    <div class="text-sm font-semibold mt-1 {{ ($dueBreakdown['netDue'] ?? 0) > 0 ? 'text-rose-700' : 'text-gray-900' }}">Rs {{ number_format((float)($dueBreakdown['netDue'] ?? 0), 2) }}</div>
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

                            @can('students.manage')
                                <div class="pt-4 border-t border-gray-200" x-data="{ openLeave:false, openDocs:false, openReadmit:false, docsValue: '{{ $student->leaving_docs_issued ? '0' : '1' }}', reason:'', allowDue:false, readmitClassRoomId:'', readmitDate:'{{ now()->toDateString() }}' }">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900">Leaving</div>
                                            <div class="mt-1">
                                                @if($student->alumni)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">Alumni</span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">Active Student</span>
                                                @endif
                                            </div>
                                        </div>
                                        @if(! $student->alumni)
                                            <button type="button" class="inline-flex items-center rounded-md bg-amber-600 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-700" x-on:click="openLeave=true; reason='';">
                                                Mark as Alumni
                                            </button>
                                        @endif
                                    </div>

                                    @if($student->alumni)
                                        <div class="mt-4 flex items-center justify-between">
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900">Leaving Certificate</div>
                                                <div class="mt-1">
                                                    @if($student->leaving_docs_issued)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">Issued</span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Not Issued</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button type="button" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50" x-on:click="openReadmit=true; reason=''; readmitClassRoomId=''; readmitDate='{{ now()->toDateString() }}';">
                                                    Re-Admit
                                                </button>
                                                <button type="button" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700" x-on:click="openDocs=true; docsValue='{{ $student->leaving_docs_issued ? '0' : '1' }}'; reason=''; allowDue=false;">
                                                    {{ $student->leaving_docs_issued ? 'Mark Not Issued' : 'Issue Now' }}
                                                </button>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Mark as Alumni Modal -->
                                    <template x-teleport="body">
                                        <div x-cloak x-show="openLeave">
                                            <div class="fixed inset-0 bg-black/40 z-[100]" x-on:click="openLeave=false"></div>
                                            <div class="fixed inset-0 z-[101] flex items-center justify-center p-4">
                                                <div class="w-[90%] max-w-md rounded-lg bg-white p-5 shadow-xl">
                                                    <div class="text-base font-semibold text-gray-800">Mark Student as Alumni</div>
                                                    <div class="mt-2 text-sm text-gray-600">This student will be marked as left school.</div>
                                                    <div class="mt-4">
                                                        <label class="block text-sm font-medium text-gray-700">Reason</label>
                                                        <textarea x-model="reason" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-amber-500 focus:ring-amber-500" rows="3" placeholder="Enter reason for leaving..." required></textarea>
                                                    </div>
                                                    <form x-ref="leaveForm" class="hidden" method="POST" action="{{ route('students.mark_alumni', $student) }}">
                                                        @csrf
                                                        <input type="hidden" name="reason" :value="reason">
                                                    </form>
                                                    <div class="mt-5 flex justify-end gap-2">
                                                        <button type="button" class="rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" x-on:click="openLeave=false; reason=''">Cancel</button>
                                                        <button type="button" class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700" x-on:click="if((reason||'').trim().length===0){return;} $refs.leaveForm.submit();">Save</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Leaving Docs Modal -->
                                    <template x-teleport="body">
                                        <div x-cloak x-show="openDocs">
                                            <div class="fixed inset-0 bg-black/40 z-[100]" x-on:click="openDocs=false"></div>
                                            <div class="fixed inset-0 z-[101] flex items-center justify-center p-4">
                                                <div class="w-[90%] max-w-md rounded-lg bg-white p-5 shadow-xl">
                                                    <div class="text-base font-semibold text-gray-800">Leaving Certificate</div>
                                                    <div class="mt-2 text-sm text-gray-600" x-text="docsValue==='1' ? 'Mark leaving certificate as issued?' : 'Mark leaving certificate as not issued?' "></div>

                                                    @php $netDue = (float)($dueBreakdown['netDue'] ?? 0); @endphp
                                                    @if($netDue > 0)
                                                        <div class="mt-3 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800">
                                                            Pending due: <span class="font-semibold">Rs {{ number_format($netDue, 2) }}</span>
                                                        </div>
                                                    @endif

                                                    <div class="mt-4">
                                                        <label class="block text-sm font-medium text-gray-700">Reason <span class="text-rose-600" x-show="docsValue==='1'">*</span></label>
                                                        <textarea x-model="reason" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="3" placeholder="Enter reason..." :required="docsValue==='1'"></textarea>
                                                    </div>

                                                    @if($netDue > 0)
                                                        <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-700" x-show="docsValue==='1'">
                                                            <input type="checkbox" x-model="allowDue" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                            Proceed even with due amount
                                                        </label>
                                                    @endif

                                                    <form x-ref="docsForm" class="hidden" method="POST" action="{{ route('students.leaving_docs', $student) }}">
                                                        @csrf
                                                        <input type="hidden" name="value" :value="docsValue">
                                                        <input type="hidden" name="reason" :value="reason">
                                                        <input type="hidden" name="allow_due" :value="allowDue ? '1' : '0'">
                                                    </form>

                                                    <div class="mt-5 flex justify-end gap-2">
                                                        <button type="button" class="rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" x-on:click="openDocs=false; reason=''; allowDue=false;">Cancel</button>
                                                        <button type="button" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" x-on:click="
                                                            if(docsValue==='1' && (reason||'').trim().length===0){return;}
                                                            @if($netDue > 0)
                                                                if(docsValue==='1' && !allowDue){return;}
                                                            @endif
                                                            $refs.docsForm.submit();
                                                        ">Save</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Re-Admit Modal -->
                                    <template x-teleport="body">
                                        <div x-cloak x-show="openReadmit">
                                            <div class="fixed inset-0 bg-black/40 z-[100]" x-on:click="openReadmit=false"></div>
                                            <div class="fixed inset-0 z-[101] flex items-center justify-center p-4">
                                                <div class="w-[90%] max-w-md rounded-lg bg-white p-5 shadow-xl">
                                                    <div class="text-base font-semibold text-gray-800">Re-Admit Student</div>
                                                    <div class="mt-2 text-sm text-gray-600">Assign a new grade and activate the student again.</div>

                                                    <div class="mt-4">
                                                        <label class="block text-sm font-medium text-gray-700">New Grade</label>
                                                        <select x-model="readmitClassRoomId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                                            <option value="">Select grade...</option>
                                                            @foreach($classRooms as $cr)
                                                                <option value="{{ $cr->id }}">{{ $cr->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="mt-3">
                                                        <label class="block text-sm font-medium text-gray-700">Re-Admit Date</label>
                                                        <input type="text" placeholder="DD-MM-YYYY" x-model="readmitDate" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                                                    </div>

                                                    <div class="mt-3">
                                                        <label class="block text-sm font-medium text-gray-700">Reason</label>
                                                        <textarea x-model="reason" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="3" placeholder="Enter reason..."></textarea>
                                                    </div>

                                                    <form x-ref="readmitForm" class="hidden" method="POST" action="{{ route('students.readmit', $student) }}">
                                                        @csrf
                                                        <input type="hidden" name="class_room_id" :value="readmitClassRoomId">
                                                        <input type="hidden" name="re_admit_date" :value="readmitDate">
                                                        <input type="hidden" name="reason" :value="reason">
                                                    </form>

                                                    <div class="mt-5 flex justify-end gap-2">
                                                        <button type="button" class="rounded-md border px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" x-on:click="openReadmit=false; reason=''; readmitClassRoomId='';">Cancel</button>
                                                        <button type="button" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" x-on:click="
                                                            if((readmitClassRoomId||'').trim().length===0){return;}
                                                            $refs.readmitForm.submit();
                                                        ">Save</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            @endcan

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
                                <div class="mt-1 text-xs text-gray-500">Start: {{ $dueBreakdown['startDate'] ? \Carbon\Carbon::parse($dueBreakdown['startDate'])->format('d-m-Y') : '-' }}</div>
                                @if(!empty($dueBreakdown['startDate']) && \Carbon\Carbon::parse($dueBreakdown['startDate'])->startOfDay()->gt(now()->startOfDay()))
                                    <div class="mt-2 text-xs text-rose-700 font-semibold">Payment Start Date is in the future, so no dues are counted yet. Edit the student and set the correct start month.</div>
                                @endif
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
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6" x-data="{ feeModalOpen: false }">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-bold text-gray-900">Paid Month Tracker</h3>
                                    @if(!empty($feeChoice['enabled']) && ($feeChoice['oldFee'] ?? 0) > 0 && ($feeChoice['newFee'] ?? 0) > 0 && ($feeChoice['oldFee'] ?? 0) != ($feeChoice['newFee'] ?? 0))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold border border-amber-200 bg-amber-50 text-amber-800" title="Old: Rs {{ number_format((float)($feeChoice['oldFee'] ?? 0), 2) }} | New: Rs {{ number_format((float)($feeChoice['newFee'] ?? 0), 2) }}">
                                            Fee changed this month (old/new)
                                        </span>
                                    @endif
                                </div>
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
                                        } elseif (($cy['status'] ?? '') === 'unpaid' && !$cy['isFuture'] && $cy['start']->copy()->startOfMonth()->lt(now()->startOfMonth())) {
                                            // Past unpaid month => overdue due
                                            $status = 'due';
                                        }

                                        $label = $cy['start']->format('M Y');
                                    @endphp
                                    <div class="relative group">
                                        @if($status === 'advance')
                                            <div class="border-2 border-indigo-500 bg-indigo-50 rounded-lg p-3 text-center hover:shadow-md transition-all" title="Advance: this month is paid in advance.">
                                                <div class="text-xs font-semibold text-indigo-700 mb-1">{{ $label }}</div>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                                </svg>
                                                <div class="text-xs font-bold text-indigo-700 mt-1">Advance</div>
                                            </div>
                                        @elseif($status === 'partial')
                                            <div class="border-2 border-orange-400 bg-orange-50 rounded-lg p-3 text-center hover:shadow-md transition-all" title="Partial: this month is not fully paid yet. Balance shown below.">
                                                <div class="text-xs font-semibold text-orange-700 mb-1">{{ $label }}</div>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 019 9v.375M10.125 2.25A3.375 3.375 0 0113.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 013.375 3.375M9 15l2.25 2.25L15 12" />
                                                </svg>
                                                <div class="text-xs font-bold text-orange-700 mt-1">Partial</div>
                                                <div class="text-[10px] font-medium text-orange-800 mt-0.5">Bal: Rs {{ number_format((float)($cy['remaining'] ?? 0), 2) }}</div>
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
                                            @if(!empty($feeChoice['enabled']) && ($feeChoice['oldFee'] ?? 0) > 0 && ($feeChoice['newFee'] ?? 0) > 0 && ($feeChoice['oldFee'] ?? 0) != ($feeChoice['newFee'] ?? 0))
                                                @can('revenue.add')
                                                    <button type="button" @click="feeModalOpen = true" class="w-full border-2 border-amber-400 bg-amber-50 rounded-lg p-3 text-center hover:shadow-md transition-all">
                                                        <div class="text-xs font-semibold text-amber-700 mb-1">{{ $label }}</div>
                                                        <svg class="h-6 w-6 text-amber-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3" />
                                                        </svg>
                                                        <div class="text-xs font-semibold text-amber-700 mt-1">Current</div>
                                                        <div class="text-[10px] font-medium text-amber-700/80 mt-0.5">Click to choose fee</div>
                                                    </button>
                                                @else
                                                    <div class="border-2 border-amber-400 bg-amber-50 rounded-lg p-3 text-center hover:shadow-md transition-all">
                                                        <div class="text-xs font-semibold text-amber-700 mb-1">{{ $label }}</div>
                                                        <svg class="h-6 w-6 text-amber-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3" />
                                                        </svg>
                                                        <div class="text-xs font-semibold text-amber-700 mt-1">Current</div>
                                                    </div>
                                                @endcan
                                            @else
                                                <div class="border-2 border-amber-400 bg-amber-50 rounded-lg p-3 text-center hover:shadow-md transition-all">
                                                    <div class="text-xs font-semibold text-amber-700 mb-1">{{ $label }}</div>
                                                    <svg class="h-6 w-6 text-amber-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3" />
                                                    </svg>
                                                    <div class="text-xs font-semibold text-amber-700 mt-1">Current</div>
                                                </div>
                                            @endif
                                        @elseif($status === 'due')
                                            <div class="border-2 border-amber-400 bg-amber-50 rounded-lg p-3 text-center hover:shadow-md transition-all" title="Due: this month is overdue and unpaid.">
                                                <div class="text-xs font-semibold text-amber-700 mb-1">{{ $label }}</div>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <div class="text-xs font-semibold text-amber-700 mt-1">Due</div>
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

                            <!-- Current month fee selection modal -->
                            <div x-show="feeModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" aria-labelledby="fee-modal-title" role="dialog" aria-modal="true">
                                <div class="absolute inset-0 bg-black/40" @click="feeModalOpen = false"></div>

                                <div class="relative w-full max-w-md rounded-xl bg-white shadow-xl border border-gray-200 p-6">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 id="fee-modal-title" class="text-lg font-bold text-gray-900">Choose fee for current month</h3>
                                            <p class="text-sm text-gray-600 mt-1">Promotion/Demotion happened this month. Select which monthly fee to apply for this month only.</p>
                                        </div>
                                        <button type="button" class="text-gray-500 hover:text-gray-700" @click="feeModalOpen = false">✕</button>
                                    </div>

                                    <div class="mt-4 space-y-3">
                                        <form method="POST" action="{{ route('students.monthly_fee.current', $student) }}">
                                            @csrf
                                            <input type="hidden" name="year" value="{{ (int)($feeChoice['year'] ?? now()->year) }}" />
                                            <input type="hidden" name="month" value="{{ (int)($feeChoice['month'] ?? now()->month) }}" />

                                            <button type="submit" name="choice" value="old" class="w-full text-left rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100 p-4">
                                                <div class="text-sm font-semibold text-gray-900">Use OLD fee</div>
                                                <div class="text-xs text-gray-600 mt-1">Rs {{ number_format((float)($feeChoice['oldFee'] ?? 0), 2) }}</div>
                                            </button>

                                            <button type="submit" name="choice" value="new" class="w-full text-left rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100 p-4 mt-3">
                                                <div class="text-sm font-semibold text-gray-900">Use NEW fee</div>
                                                <div class="text-xs text-gray-600 mt-1">Rs {{ number_format((float)($feeChoice['newFee'] ?? 0), 2) }}</div>
                                            </button>
                                        </form>
                                    </div>

                                    @if(($feeChoice['overrideFee'] ?? 0) > 0)
                                        <div class="mt-4 text-xs text-gray-600">Current selection: Rs {{ number_format((float)$feeChoice['overrideFee'], 2) }}</div>
                                    @endif

                                    <div class="mt-4 flex justify-end">
                                        <button type="button" class="px-4 py-2 text-sm font-semibold rounded-lg border border-gray-200 bg-white hover:bg-gray-50" @click="feeModalOpen = false">Cancel</button>
                                    </div>
                                </div>
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
                                <input type="text" name="from" placeholder="DD-MM-YYYY" value="{{ $filters['from'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 uppercase">To</label>
                                <input type="text" name="to" placeholder="DD-MM-YYYY" value="{{ $filters['to'] ?? '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
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
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Refunded</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Waived</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Net</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse ($payments as $p)
                                        @php
                                            $refunded = (float) ($p->refunded_amount ?? 0);
                                            $waived = (float) ($p->waived_amount ?? 0);
                                            $net = max(0.0, (float) $p->amount - $refunded);
                                        @endphp
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $p->bill_no ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ optional($p->paid_at)->format('d-m-Y') }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $p->category?->name ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">{{ number_format((float) $p->amount, 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-right font-semibold {{ $refunded > 0 ? 'text-rose-700' : 'text-gray-500' }}">{{ number_format($refunded, 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-right font-semibold {{ $waived > 0 ? 'text-indigo-700' : 'text-gray-500' }}">{{ number_format($waived, 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">{{ number_format($net, 2) }}</td>
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
                                                        @if(!empty($p->bill_no))
                                                            <a href="{{ route('revenue.adjustments.index', ['bill_no' => $p->bill_no]) }}" class="hover:text-indigo-600" title="Refund / Waiver">
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                                                </svg>
                                                            </a>
                                                        @endif
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="px-4 py-6 text-center text-sm text-gray-600" colspan="8">No payments found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">{{ $payments->links() }}</div>
                    </div>

                    <!-- Seminar Attendance & Payments -->
                    @isset($seminarEnrollments)
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                        <div class="flex items-center justify-between gap-4 mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Seminar Attendance & Payments</h3>
                                <p class="text-sm text-gray-600 mt-1">History of seminar participation and payments</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Seminar</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Scheduled</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Present</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Paid</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Amount</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Paid At</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse($seminarEnrollments as $en)
                                        @php
                                            $seminar = $en->seminar;
                                            $scheduled = $seminar?->date ?? $seminar?->starts_at ?? null;
                                            $amount = (float) ($en->amount ?? 0);
                                        @endphp
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $seminar?->title ?? $seminar?->name ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $scheduled ? \Carbon\Carbon::parse($scheduled)->format('d-m-Y') : '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-center">
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $en->present ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">
                                                    {{ $en->present ? 'Yes' : 'No' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-center">
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $en->paid ? 'bg-green-100 text-green-700' : 'bg-rose-100 text-rose-700' }}">
                                                    {{ $en->paid ? 'Paid' : 'Unpaid' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">{{ number_format($amount, 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ optional($en->paid_at)->format('d-m-Y') ?: '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-right">
                                                @if($seminar)
                                                    <a href="{{ route('seminars.show', $seminar) }}" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M5 7h14M5 5a2 2 0 012-2h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5z" />
                                                        </svg>
                                                        View
                                                    </a>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="px-4 py-6 text-center text-sm text-gray-600" colspan="7">No seminar records found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">{{ $seminarEnrollments->links() }}</div>
                    </div>
                    @endisset

                    <!-- Refund / Waiver History -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6">
                        <div class="flex items-center justify-between gap-4 mb-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900">Refund / Waiver History</h3>
                                <p class="text-sm text-gray-600 mt-1">All refund and waiver adjustments for this student</p>
                            </div>
                            @can('revenue.manage')
                                <a href="{{ route('revenue.adjustments.index', ['q' => $student->admission_number ?: $student->name]) }}" class="inline-flex items-center gap-2 px-3 py-2 bg-white text-gray-700 text-sm font-semibold rounded-lg border border-gray-200 hover:bg-gray-50">
                                    Open Refund / Waiver
                                </a>
                            @endcan
                        </div>

                        <div class="overflow-x-auto rounded-lg border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Bill</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Type</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Amount</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Reason</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">By</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @forelse($adjustments as $a)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ optional($a->created_at)->format('d-m-Y') }}</td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $a->revenue?->bill_no ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $a->type === 'refund' ? 'bg-rose-100 text-rose-700' : 'bg-indigo-100 text-indigo-700' }}">
                                                    {{ ucfirst($a->type) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-right font-semibold {{ $a->type === 'refund' ? 'text-rose-700' : 'text-indigo-700' }}">{{ number_format((float) $a->amount, 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $a->reason ?: '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $a->creator?->name ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-right">
                                                <div class="inline-flex items-center gap-3 text-gray-500">
                                                    @if($a->revenue)
                                                        <a href="{{ route('revenue.items.receipt', $a->revenue) }}" class="hover:text-indigo-600" title="View Receipt">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                                                            </svg>
                                                        </a>
                                                    @endif
                                                    @if($a->type === 'refund')
                                                        <a href="{{ route('printer.refund', $a) }}" class="hover:text-indigo-600" title="Print Refund Slip">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V2.25A.75.75 0 016.75 1.5h10.5a.75.75 0 01.75.75V9m-12 0h12m-12 0a3 3 0 00-3 3v5.25A.75.75 0 003.75 21h16.5a.75.75 0 00.75-.75V12a3 3 0 00-3-3m-12 8.25h12V14.25H6v3z" />
                                                            </svg>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="px-4 py-6 text-center text-sm text-gray-600" colspan="7">No refund/waiver adjustments found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">{{ $adjustments->links() }}</div>
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
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ optional($h->created_at)->format('d-m-Y') }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                @php
                                                    $action = (string) $h->action;
                                                    $label = $action === 'promote' ? 'Promoted' : ($action === 'demote' ? 'Demoted' : ($action === 'leave' ? 'Left School' : ($action === 'readmit' ? 'Re-Admitted' : ucfirst($action))));
                                                    $badgeCls = $action === 'promote'
                                                        ? 'bg-emerald-100 text-emerald-700'
                                                        : ($action === 'demote'
                                                            ? 'bg-rose-100 text-rose-700'
                                                            : ($action === 'readmit'
                                                                ? 'bg-indigo-100 text-indigo-700'
                                                                : 'bg-amber-100 text-amber-800'));
                                                @endphp
                                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $badgeCls }}">
                                                    {{ $label }}
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
