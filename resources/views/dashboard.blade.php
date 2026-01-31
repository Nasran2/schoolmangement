<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-900">{{ __('Dashboard') }}</h2>
                <div class="mt-1 text-sm text-gray-600">Academic Year: <span class="font-semibold text-gray-800">{{ $selectedAcademicYear ?? '' }}</span></div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <form method="GET" class="flex items-center gap-2">
                    <span class="text-sm text-gray-600">Range</span>
                    <select name="range" class="border-gray-300 rounded-md shadow-sm text-sm" onchange="this.form.submit()">
                        <option value="today" @selected(($dashboardRange['key'] ?? '') === 'today')>Today</option>
                        <option value="last_7_days" @selected(($dashboardRange['key'] ?? '') === 'last_7_days')>Last 7 Days</option>
                        <option value="last_30_days" @selected(($dashboardRange['key'] ?? '') === 'last_30_days')>Last 30 Days</option>
                        <option value="this_month" @selected(($dashboardRange['key'] ?? '') === 'this_month')>This Month</option>
                        <option value="last_month" @selected(($dashboardRange['key'] ?? '') === 'last_month')>Last Month</option>
                        <option value="this_year" @selected(($dashboardRange['key'] ?? '') === 'this_year')>This Year</option>
                        <option value="last_year" @selected(($dashboardRange['key'] ?? '') === 'last_year')>Last Year</option>
                        <option value="current_financial_year" @selected(($dashboardRange['key'] ?? '') === 'current_financial_year')>Current Financial Year</option>
                        <option value="last_financial_year" @selected(($dashboardRange['key'] ?? '') === 'last_financial_year')>Last Financial Year</option>
                        <option value="custom" @selected(($dashboardRange['key'] ?? '') === 'custom')>Custom</option>
                    </select>
                    <div class="flex gap-2" x-data="{ show: '{{ $dashboardRange['key'] ?? '' }}' === 'custom' }" x-init="show = ('{{ $dashboardRange['key'] ?? '' }}' === 'custom')">
                        <input type="text" name="from" placeholder="DD-MM-YYYY" class="border-gray-300 rounded-md shadow-sm text-sm" value="{{ $dashboardRange['from'] ?? '' }}" x-show="show" x-cloak>
                        <input type="text" name="to" placeholder="DD-MM-YYYY" class="border-gray-300 rounded-md shadow-sm text-sm" value="{{ $dashboardRange['to'] ?? '' }}" x-show="show" x-cloak>
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-gray-900 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700" x-show="show" x-cloak>Apply</button>
                    </div>
                </form>

                <div class="flex items-center gap-2">
                    @can('revenue.add')
                        <a href="{{ route('revenue.items.create') }}" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow hover:bg-indigo-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
                            Add Revenue
                        </a>
                    @endcan
                    @can('expense.add')
                        <a href="{{ route('expense.items.create') }}" class="inline-flex items-center gap-2 rounded-md bg-rose-600 px-3 py-2 text-xs font-semibold text-white shadow hover:bg-rose-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v4H3z"/><path d="M8 7v14"/><path d="M16 7v14"/></svg>
                            Add Expense
                        </a>
                    @endcan
                    @can('sms.send.bulk')
                        <a href="{{ route('students.index') }}" class="inline-flex items-center gap-2 rounded-md bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-800 shadow hover:bg-gray-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10"/><path d="M3 15l9 6 9-6"/></svg>
                            View Due & SMS
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <script type="application/json" id="dashboard-data">{!! json_encode($dashboardData ?? []) !!}</script>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            @can('dashboard.widget.total_revenue.view')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18"/><path d="M12 3v18"/></svg>
                        </span>
                        <div>
                            <div class="text-sm text-gray-500">Total Revenue</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900">Rs {{ number_format($totalRevenue ?? 0, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endcan

            @can('dashboard.widget.total_expenses.view')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v4H3z"/><path d="M8 7v14"/><path d="M16 7v14"/></svg>
                        </span>
                        <div>
                            <div class="text-sm text-gray-500">Total Expenses</div>
                            <div class="mt-1 text-2xl font-semibold text-gray-900">Rs {{ number_format($totalExpenses ?? 0, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endcan

            @can('dashboard.widget.net_profit.view')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full {{ ($netProfit ?? 0) >= 0 ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 12h16"/><path d="M12 4l8 8-8 8"/></svg>
                        </span>
                        <div>
                            <div class="text-sm text-gray-500">Net Profit / Loss</div>
                            <div class="mt-1 text-2xl font-semibold {{ ($netProfit ?? 0) >= 0 ? 'text-green-700' : 'text-red-700' }}">Rs {{ number_format($netProfit ?? 0, 2) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endcan

            @can('dashboard.widget.cash_flow.view')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-sm text-gray-500">Cash Flow (Last 30 Days)</div>
                    <div class="mt-1 text-xs text-gray-600">Daily net (income - expense)</div>
                    <div class="mt-3 h-16">
                        <canvas id="cashflowChart"></canvas>
                    </div>
                </div>
            </div>
            @endcan
        </div>

        @if(($pendingChequeCount ?? 0) > 0)
            <div class="mt-6" x-data="{ open: true }" x-show="open" x-cloak>
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
                            </span>
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-amber-900">Pending Cheque Confirmation</div>
                                <div class="mt-1 text-sm text-amber-900/80">
                                    <span class="font-semibold">{{ $pendingChequeCount }}</span> pending cheque{{ ($pendingChequeCount ?? 0) > 1 ? 's' : '' }} (Total: <span class="font-semibold">Rs {{ number_format($pendingChequeTotal ?? 0, 2) }}</span>)
                                    @if(($pendingChequeDueCount ?? 0) > 0)
                                        <span class="ml-2 inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">{{ $pendingChequeDueCount }} due now</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            @can('revenue.manage')
                                <a href="{{ route('revenue.cheques.index', ['status' => 'pending']) }}" class="inline-flex items-center rounded-md bg-amber-700 px-3 py-2 text-xs font-semibold text-white shadow hover:bg-amber-800">
                                    View Pending Cheques
                                </a>
                            @endcan
                            <button type="button" class="rounded-md p-2 text-amber-800 hover:bg-amber-100" @click="open = false" aria-label="Dismiss">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    @if(($pendingCheques ?? collect())->count() > 0)
                        <div class="mt-4 overflow-hidden rounded-md border border-amber-200 bg-white">
                            <div class="divide-y">
                                @foreach(($pendingCheques ?? collect()) as $item)
                                    <div class="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold text-gray-900">
                                                Rs {{ number_format($item->amount ?? 0, 2) }}
                                                <span class="ml-2 text-xs font-normal text-gray-600">Bill: {{ $item->bill_no ?? '—' }}</span>
                                            </div>
                                            <div class="mt-0.5 text-xs text-gray-700">
                                                Cheque Date: <span class="font-semibold">{{ $item->cheque_date ? \Carbon\Carbon::parse($item->cheque_date)->format('d M Y') : '—' }}</span>
                                                <span class="mx-2">•</span>
                                                No: <span class="font-semibold">{{ data_get($item->payment_meta, 'cheque_number') ?? '—' }}</span>
                                                <span class="mx-2">•</span>
                                                Bank: <span class="font-semibold">{{ data_get($item->payment_meta, 'bank') ?? '—' }}</span>
                                            </div>
                                            @if($item->cheque_date)
                                                @php
                                                    $cd = \Carbon\Carbon::parse($item->cheque_date)->startOfDay();
                                                    $autoOn = $cd->copy()->addDays((int)($chequeAutoPassDays ?? 14));
                                                    $daysToAuto = \Carbon\Carbon::today()->diffInDays($autoOn, false);
                                                @endphp
                                                <div class="mt-0.5 text-xs text-gray-700">
                                                    Auto-pass: 
                                                    @if($daysToAuto > 0)
                                                        <span class="font-semibold">in {{ $daysToAuto }} day{{ $daysToAuto > 1 ? 's' : '' }}</span>
                                                    @elseif($daysToAuto === 0)
                                                        <span class="font-semibold text-amber-700">today</span>
                                                    @else
                                                        <span class="font-semibold text-rose-700">overdue (should be auto-passed)</span>
                                                    @endif
                                                </div>
                                            @endif
                                            <div class="mt-0.5 text-xs text-gray-700">
                                                Student: <span class="font-semibold">{{ $item->student?->name ?? (data_get($item->payment_meta, 'student_name') ?? '—') }}</span>
                                                @if($item->student)
                                                    <span class="ml-2 text-[11px] text-gray-500">ID: {{ $item->student->admission_number ?? $item->student->id }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            @can('revenue.manage')
                                                <form method="POST" action="{{ route('revenue.items.cheque.passed', $item) }}" onsubmit="return confirm('Mark this cheque as PASSED? It will be confirmed and counted as paid on the cheque date.');">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow hover:bg-emerald-700">Passed</button>
                                                </form>
                                                <form method="POST" action="{{ route('revenue.items.cheque.returned', $item) }}" onsubmit="return confirm('Mark this cheque as RETURNED? It will be rejected and will NOT count as paid.');">
                                                    @csrf
                                                    <button type="submit" class="inline-flex items-center rounded-md bg-rose-600 px-3 py-2 text-xs font-semibold text-white shadow hover:bg-rose-700">Returned</button>
                                                </form>
                                                <a class="inline-flex items-center rounded-md bg-gray-900 px-3 py-2 text-xs font-semibold text-white shadow hover:bg-gray-700" href="{{ route('revenue.items.edit', $item) }}">Open</a>
                                            @endcan
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
            @can('dashboard.widget.revenue_vs_expense.view')
            <div class="xl:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-base font-semibold text-gray-900">Revenue vs Expense ({{ $dashboardRange['label'] ?? 'Selected Range' }})</div>
                    <div class="mt-4 h-72">
                        <canvas id="monthlyBarChart"></canvas>
                    </div>
                </div>
            </div>
            @endcan

            @can('dashboard.widget.due_students.view')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-base font-semibold text-gray-900">Students with Due Payments</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">{{ ($dueStudents ?? collect())->count() }}</div>
                    <div class="mt-4 divide-y rounded border">
                        @forelse(($dueStudents ?? collect()) as $student)
                            <div class="flex items-center justify-between px-4 py-3">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">{{ strtoupper(substr($student->name,0,1)) }}</span>
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium text-gray-900 truncate">{{ $student->name }}</div>
                                        <div class="text-xs text-gray-600 truncate">{{ $student->class }} {{ $student->year ? '· '.$student->year : '' }}</div>
                                    </div>
                                </div>
                                <div class="text-sm font-semibold text-red-600">Rs {{ number_format($student->computed_due_amount ?? $student->due_amount, 2) }}</div>
                            </div>
                        @empty
                            <div class="px-4 py-3 text-sm text-gray-600">No overdue payments.</div>
                        @endforelse
                    </div>
                    <div class="mt-4 flex gap-2">
                        @can('students.manage')
                            <a class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700" href="{{ route('students.index') }}">View All Due Payments</a>
                        @endcan
                        @can('sms.send.bulk')
                            <button type="button" disabled class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-500 uppercase tracking-widest cursor-not-allowed">Send Bulk SMS</button>
                        @endcan
                    </div>
                </div>
            </div>
            @endcan
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
            @can('dashboard.widget.revenue_category_breakdown.view')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-base font-semibold text-gray-900">Revenue Category Breakdown</div>
                    <div class="mt-4 h-64">
                        <canvas id="revDonut"></canvas>
                    </div>
                </div>
            </div>
            @endcan

            @can('dashboard.widget.expense_category_breakdown.view')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-base font-semibold text-gray-900">Expense Category Breakdown</div>
                    <div class="mt-4 h-64">
                        <canvas id="expDonut"></canvas>
                    </div>
                </div>
            </div>
            @endcan

            @can('dashboard.widget.upcoming_teacher_payments.view')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-base font-semibold text-gray-900">Upcoming Teacher Salary Payments</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">{{ ($dueTeachers ?? collect())->count() }}</div>
                    <div class="mt-1 text-sm font-semibold text-red-600">Rs {{ number_format($dueTeachersTotal ?? 0, 2) }}</div>
                    @if(!empty($salaryDeadline))
                        <div class="mt-1 text-xs text-gray-600">Deadline: {{ \Carbon\Carbon::parse($salaryDeadline)->format('d M Y') }}</div>
                    @endif
                    <div class="mt-4 divide-y rounded border">
                        @forelse(($dueTeachers ?? collect()) as $teacher)
                            <div class="flex items-center justify-between px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-700 text-xs font-semibold">{{ strtoupper(substr($teacher->name,0,1)) }}</span>
                                    <div class="text-sm font-medium text-gray-900">{{ $teacher->name }}</div>
                                </div>
                                <div class="text-sm font-semibold text-gray-800">Rs {{ number_format($teacher->salary_amount ?? 0, 2) }}</div>
                            </div>
                        @empty
                            <div class="px-4 py-3 text-sm text-gray-600">No upcoming salary payments.</div>
                        @endforelse
                    </div>
                    <div class="mt-4">
                        @canany(['teachers.salary.pay','teachers.salary.summary.view'])
                            <a class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700" href="{{ route('teacher-salary-payments.summary') }}">View Due & Upcoming</a>
                        @endcanany
                    </div>
                </div>
            </div>
            @endcan
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
            @can('dashboard.widget.enrollment_trend.view')
            <div class="xl:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-base font-semibold text-gray-900">Student Enrollment Trend (Last 12 Months)</div>
                    <div class="mt-4 h-72">
                        <canvas id="enrollmentLine"></canvas>
                    </div>
                </div>
            </div>
            @endcan

            @can('dashboard.widget.notifications.view')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-base font-semibold text-gray-900">System Notifications & Reminders</div>
                    <div class="mt-4 space-y-2">
                        @forelse(($alerts ?? collect()) as $alert)
                            <div class="rounded border px-3 py-2 text-sm text-gray-700">{{ $alert }}</div>
                        @empty
                            <div class="text-sm text-gray-600">No notifications.</div>
                        @endforelse
                    </div>
                </div>
            </div>
            @endcan
        </div>

        @can('dashboard.widget.recent_activity.view')
        <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-base font-semibold text-gray-900">Recent Financial Activity</div>
                    <div class="mt-4 divide-y rounded border">
                        @forelse(($recentActivity ?? collect()) as $item)
                            <div class="flex items-center justify-between px-4 py-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-900 truncate">{{ $item['label'] }}</div>
                                    <div class="text-xs text-gray-600">{{ $item['type'] }}</div>
                                </div>
                                <div class="text-sm font-semibold {{ ($item['direction'] ?? '') === 'in' ? 'text-green-700' : 'text-red-700' }}">
                                    {{ ($item['direction'] ?? '') === 'in' ? '+' : '-' }} Rs {{ number_format($item['amount'] ?? 0, 2) }}
                                </div>
                            </div>
                        @empty
                            <div class="px-4 py-3 text-sm text-gray-600">No activity yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="text-base font-semibold text-gray-900">Recent Activity</div>
                        <form method="GET" class="flex items-end gap-2">
                            <div>
                                <label class="block text-xs text-gray-600">Range</label>
                                <select name="activity_range" class="border-gray-300 rounded-md shadow-sm text-xs">
                                    <option value="today" @selected(($activityFilters['range'] ?? '')==='today')>Today</option>
                                    <option value="last_7_days" @selected(($activityFilters['range'] ?? '')==='last_7_days')>Last 7 Days</option>
                                    <option value="last_30_days" @selected(($activityFilters['range'] ?? '')==='last_30_days')>Last 30 Days</option>
                                    <option value="this_month" @selected(($activityFilters['range'] ?? '')==='this_month')>This Month</option>
                                    <option value="this_year" @selected(($activityFilters['range'] ?? '')==='this_year')>This Year</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600">Action</label>
                                <select name="activity_action" class="border-gray-300 rounded-md shadow-sm text-xs">
                                    <option value="">All</option>
                                    @foreach(($activityFilters['actions'] ?? collect()) as $act)
                                        <option value="{{ $act }}" @selected(($activityFilters['action'] ?? '')===$act)>{{ $act }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600">User</label>
                                <select name="activity_user" class="border-gray-300 rounded-md shadow-sm text-xs">
                                    <option value="">All</option>
                                    @foreach(($activityFilters['users'] ?? collect()) as $u)
                                        <option value="{{ $u->id }}" @selected(($activityFilters['user'] ?? '')==$u->id)>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600">Search</label>
                                <input type="text" name="activity_q" value="{{ $activityFilters['q'] ?? '' }}" placeholder="description" class="border-gray-300 rounded-md shadow-sm text-xs">
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="inline-flex items-center px-3 py-2 bg-gray-900 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Apply</button>
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-3 py-2 bg-gray-100 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-200">Reset</a>
                            </div>
                        </form>
                    </div>

                    <div class="mt-4 divide-y rounded border">
                        @forelse(($recentAuditLogs ?? collect()) as $log)
                            <div class="px-4 py-3 flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 text-sm text-gray-600">
                                        <span class="font-medium text-gray-900">{{ optional($log->created_at)->format('M d, H:i') }}</span>
                                        <span>·</span>
                                        <span>{{ $log->user?->name ?? 'System' }}</span>
                                        <span>·</span>
                                        <span class="text-xs rounded bg-gray-100 px-2 py-0.5">{{ $log->action }}</span>
                                    </div>
                                    <div class="mt-1 text-sm text-gray-700 truncate">{{ $log->description }}</div>
                                    <div class="mt-1 text-xs text-gray-500 truncate">{{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }} · IP {{ $log->ip_address }}</div>
                                </div>
                                @if(!empty($log->metadata))
                                    <div class="text-xs text-gray-500 max-w-xs truncate">{{ json_encode($log->metadata) }}</div>
                                @endif
                            </div>
                        @empty
                            <div class="px-4 py-3 text-sm text-gray-600">No recent activity.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        @endcan
    </div>
</x-app-layout>
