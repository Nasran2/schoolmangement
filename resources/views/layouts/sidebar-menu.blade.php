    <nav class="flex-1 overflow-y-auto px-3 py-4" x-data="{ menus: { revenue: @js($isRevenue), expense: @js($isExpense), students: @js($isStudents), teachers: @js($isTeachers), reports: @js($isReports), classrooms: @js($isClassrooms), settings: @js($isSettings), evaluations: @js($isEvaluations), seminars: false, extraClasses: false } }">
        @if($canViewDashboard)
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ $isDashboard ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12l9-9 9 9"/><path d="M9 21V9h6v12"/></svg>
                <span>Dashboard</span>
            </a>
        @endif

        @if($canManageRevenue)
            <a href="{{ route('opening-balance.create') }}"
               class="mt-2 flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('opening-balance.create') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="2" x2="12" y2="22"></line><path d="M17 5H9a3 3 0 0 0 0 6h6a3 3 0 0 1 0 6H6"/></svg>
                <span>Opening Balance</span>
            </a>
        @endif

        @can('owner_transfers.manage')
            <a href="{{ route('owner-transfers.index') }}"
               class="mt-2 flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('owner-transfers.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18m-5-5 5 5-5 5M3 5h7M3 19h7"/></svg>
                <span>Owner Bank Transfer</span>
            </a>
        @endcan

        @if($canAddRevenue || $canManageRevenue || $canManageRevenueCategories)
            <div class="mt-3">
                <button type="button" @click="menus.revenue = !menus.revenue" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isRevenue ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22"/><path d="M17 5H9a3 3 0 0 0 0 6h6a3 3 0 0 1 0 6H6"/></svg>
                        <span>Revenue</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="menus.revenue ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="menus.revenue" class="mt-1 space-y-1 pl-8" x-cloak>
                          @if($canAddRevenue)
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('revenue.items.create') && !request()->has('quick') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('revenue.items.create') }}">Add</a>
                        <a class="block px-3 py-2 rounded-md text-sm {{ (request()->routeIs('revenue.items.create') && request()->get('quick')==='monthly') ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200' : 'text-indigo-700 hover:bg-indigo-50' }}" href="{{ route('revenue.items.create', ['quick' => 'monthly']) }}">Quick Monthly Payment</a>
                        @foreach(['exam' => 'Collect Exam Fee', 'facility' => 'Collect Facility Fee'] as $feeType => $feeTitle)
                            <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('revenue.items.create') && request()->query('quick') === $feeType ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('revenue.items.create', ['quick' => $feeType]) }}">{{ $feeTitle }}</a>
                        @endforeach
                          @endif
                          @if($canManageRevenue)
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('revenue.items.*') && !request()->routeIs('revenue.items.create') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('revenue.items.index') }}">Manage</a>
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('revenue.cheques.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('revenue.cheques.index') }}">Cheque Payments</a>
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('revenue.reminders.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('revenue.reminders.index') }}">Reminders</a>
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('revenue.adjustments.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('revenue.adjustments.index') }}">Refund / Waiver</a>
                          @endif
                          @if($canManageRevenueCategories)
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('revenue.categories.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('revenue.categories.index') }}">Categories</a>
                          @endif
                </div>
            </div>
        @endif

        @canany(['class_fees.view', 'revenue.add', 'revenue.manage', 'revenue.categories.manage'])
            <div class="mt-2" x-data="{ feesOpen: @js(request()->routeIs('class-fees.*')) }">
                <button type="button" @click="feesOpen = !feesOpen" :aria-expanded="feesOpen" class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('class-fees.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3"><svg width="20" height="20" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4zM8 8h8M8 12h8M8 16h5"/></svg><span>Exam &amp; Facility Fees</span></span>
                    <svg width="16" height="16" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" :style="feesOpen ? 'transform:rotate(180deg)' : ''"><path d="m5 7 5 5 5-5H5z"/></svg>
                </button>
                <div x-show="feesOpen" x-cloak class="ml-8 mt-1 space-y-1">
                    <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('class-fees.index') && request('view') !== 'students' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('class-fees.index') }}">Overview</a>
                    <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('class-fees.index') && request('view') === 'students' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('class-fees.index', ['view' => 'students']) }}">All students</a>
                    @can('revenue.add')<a class="block px-3 py-2 rounded-md text-sm text-gray-700 hover:bg-gray-50" href="{{ route('class-fees.bulk') }}">Bulk payments</a>@endcan
                    @can('revenue.delete')<a class="block px-3 py-2 rounded-md text-sm text-gray-700 hover:bg-gray-50" href="{{ route('class-fees.bulk', ['mode' => 'unpaid']) }}">Make unpaid</a>@endcan
                </div>
            </div>
        @endcanany

        @if($canAddExpense || $canManageExpense || $canManageExpenseCategories)
            <div class="mt-2">
                <button type="button" @click="menus.expense = !menus.expense" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isExpense ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8c-3.314 0-6 1.343-6 3s2.686 3 6 3 6-1.343 6-3-2.686-3-6-3z"/><path d="M6 11v6c0 1.657 2.686 3 6 3s6-1.343 6-3v-6"/><path d="M6 17c0 1.657 2.686 3 6 3s6-1.343 6-3"/></svg>
                        <span>Expense</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="menus.expense ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="menus.expense" class="mt-1 space-y-1 pl-8" x-cloak>
                    @if($canAddExpense)
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('expense.items.create') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('expense.items.create') }}">Add</a>
                    @endif
                    @if($canManageExpense)
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('expense.items.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('expense.items.index') }}">Manage</a>
                    @endif
                    @if($canManageExpenseCategories)
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('expense.categories.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('expense.categories.index') }}">Categories</a>
                    @endif
                    @can('reports.view')
                        @can('reports.financial.view')
                            <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.financial') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.financial') }}">Revenue vs Expense</a>
                        @endcan
                    @endcan
                </div>
            </div>
        @endif

        @if($canAddStudents || $canManageStudents || $canBulkUploadStudents || $canReadDirectory)
            <div class="mt-2">
                <x-sidebar-section-header name="students" label="Students" :active="$isStudents" :href="$canReadDirectory ? route('hr.students.index') : ($canManageStudents ? route('students.index') : null)">
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="8" cy="8" r="3"/><circle cx="17" cy="8" r="3"/><path d="M2 21v-2a6 6 0 0112 0v2m1-7a5 5 0 015 5v2"/></svg>
                </x-sidebar-section-header>
                <div x-show="menus.students" class="mt-1 space-y-1 pl-8" x-cloak>
                    @can('hr.directory.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('hr.students.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('hr.students.index') }}">Student Details</a>
                    @endcan
                    @can('students.add')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('students.create') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('students.create') }}">Add</a>
                    @endcan
                    @can('students.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('students.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('students.index') }}">Manage</a>
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('students.alumni*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('students.alumni') }}">Alumni</a>
                    @endcan
                    @can('students.bulk_upload')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('students.bulk.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('students.bulk.create') }}">Bulk Upload</a>
                    @endcan
                </div>
            </div>
        @endif

        @if($canAddTeachers || $canManageTeachers || $user?->canAny(['teachers.bulk_upload', 'teachers.salary.pay', 'teachers.salary.summary.view', 'visiting_teachers.manage']) || $user?->hasRole('Developer') || $canReadDirectory || $canViewEvaluations)
            <div class="mt-2">
                <x-sidebar-section-header name="teachers" label="Teachers" :active="$isTeachers" :href="$canReadDirectory ? route('hr.teachers.index') : ($canManageTeachers ? route('teachers.index') : null)">
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0116 0v2"/></svg>
                </x-sidebar-section-header>
                <div x-show="menus.teachers" class="mt-1 space-y-1 pl-8" x-cloak>
                    @can('hr.directory.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('hr.teachers.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('hr.teachers.index') }}">Teacher Details</a>
                    @endcan
                    @can('teacher_evaluations.view')
                        <a class="block px-3 py-2 rounded-md text-sm text-gray-700 hover:bg-gray-50" href="{{ route('teacher-evaluations.index') }}">Evaluation reports</a>
                    @endcan
                    @can('teachers.add')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('teachers.create') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('teachers.create') }}">Add</a>
                    @endcan
                    @can('teachers.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('teachers.index') || request()->routeIs('teachers.show') || request()->routeIs('teachers.edit') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('teachers.index') }}">Manage</a>
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('teachers.report') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('teachers.report') }}">Teacher Report</a>
                    @endcan
                    @can('teachers.salary.pay')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('teacher-salary-payments.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('teacher-salary-payments.index') }}">Salary Payments</a>
                    @endcan
                    @canany(['teachers.salary.pay','teachers.salary.summary.view'])
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('teacher-salary-payments.summary') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('teacher-salary-payments.summary') }}">Salary Due & Upcoming</a>
                    @endcanany
                    @can('teachers.bulk_upload')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('teachers.bulk.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('teachers.bulk.create') }}">Bulk Upload</a>
                    @endcan
                    @if($user?->hasRole('Developer') || $user?->can('visiting_teachers.manage'))
                    <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('visiting-teachers.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('visiting-teachers.index') }}">Visiting Teachers</a>
                    @endif
                </div>
            </div>
        @endif

        @include('hr.evaluation-navigation')

        @canany(['classrooms.view','classrooms.create','classrooms.update','classrooms.delete'])
            <div class="mt-2">
                <button type="button" @click="menus.classrooms = !menus.classrooms" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isClassrooms ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"/><path d="M4 10h16"/><path d="M4 14h16"/><path d="M4 18h16"/></svg>
                        <span>Class Rooms</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="menus.classrooms ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="menus.classrooms" class="mt-1 space-y-1 pl-8" x-cloak>
                    @can('classrooms.create')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('classrooms.create') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('classrooms.create') }}">Add</a>
                    @endcan
                    @can('classrooms.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ (request()->routeIs('classrooms.index') || request()->routeIs('classrooms.edit')) ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('classrooms.index') }}">Manage</a>
                    @endcan
                </div>
            </div>
        @endcanany

        @if(auth()->user()?->hasRole('Developer'))
            <a href="{{ route('developer.dashboard') }}"
               class="mt-2 flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ $isDeveloper ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9.75 3.75h4.5v3h-4.5z"/><path d="M4.5 9.75h15v10.5h-15z"/><path d="M9 14.25h6"/><path d="M12 11.25v6"/></svg>
                <span>Developer Console</span>
            </a>

            <div class="mt-1 space-y-1 pl-8">
                <a href="{{ route('developer.students') }}" class="block px-3 py-2 rounded-md text-sm {{ $isDeveloperStudents ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">Students</a>
                <a href="{{ route('developer.teachers') }}" class="block px-3 py-2 rounded-md text-sm {{ $isDeveloperTeachers ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">Teachers</a>
                <a href="{{ route('developer.users') }}" class="block px-3 py-2 rounded-md text-sm {{ $isDeveloperUsers ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">Users</a>
            </div>
        @endif

        @if($user?->hasRole('Developer') || $user?->can('seminars.manage'))
        <!-- Seminars -->
        <div class="mt-2">
            <button type="button" @click="menus.seminars = !menus.seminars" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                <span class="flex items-center gap-3">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"/><path d="M6 21v-2a6 6 0 0112 0v2"/></svg>
                    <span>Seminars</span>
                </span>
                <svg class="h-4 w-4 transition" :class="menus.seminars ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
            <div x-show="menus.seminars" class="mt-1 space-y-1 pl-8" x-cloak>
                <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('seminars.create') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('seminars.create') }}">Add New Seminar</a>
                <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('seminars.index') || request()->routeIs('seminars.edit') || request()->routeIs('seminars.show') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('seminars.index') }}">Manage</a>
                <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('seminars.payments') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('seminars.index') }}">Payment</a>
                <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('seminars.reports.due') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('seminars.reports.due') }}">Due Payment Report</a>
            </div>
        </div>

        @endif

        @if($user?->hasRole('Developer') || $user?->can('extra_classes.manage'))
        <!-- Extra Classes -->
        <div class="mt-2">
            <button type="button" @click="menus.extraClasses = !menus.extraClasses" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                <span class="flex items-center gap-3">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"/><path d="M4 10h16"/><path d="M4 14h16"/><path d="M4 18h16"/></svg>
                    <span>Extra Classes</span>
                </span>
                <svg class="h-4 w-4 transition" :class="menus.extraClasses ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
            </button>
            <div x-show="menus.extraClasses" class="mt-1 space-y-1 pl-8" x-cloak>
                <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('extra-classes.create') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('extra-classes.create') }}">Add Extra Class</a>
                <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('extra-classes.index') || request()->routeIs('extra-classes.edit') || request()->routeIs('extra-classes.show') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('extra-classes.index') }}">Manage</a>
            </div>
        </div>

        @endif

        @if($canViewReports)
            <div class="mt-2">
                <button type="button" @click="menus.reports = !menus.reports" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isReports ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 13h3v6H7z"/><path d="M12 9h3v10h-3z"/><path d="M17 5h3v14h-3z"/></svg>
                        <span>Reports</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="menus.reports ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="menus.reports" class="mt-1 space-y-1 pl-8" x-cloak>
                    <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.index') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.index') }}">All Reports</a>
                    <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.students') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.students') }}">Students Report</a>
                    @can('reports.download')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.exports') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.exports') }}">Advanced Exports</a>
                    @endcan
                    @can('reports.revenue.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.revenue') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.revenue') }}">Revenue</a>
                    @endcan
                    @can('reports.expense.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.expense') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.expense') }}">Expense</a>
                    @endcan
                    @can('reports.outflows.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.outflows') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.outflows') }}">All Outflows</a>
                    @endcan
                    @can('reports.financial.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.financial') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.financial') }}">Financial</a>
                    @endcan
                    @can('reports.daily_ledger.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.daily_ledger') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.daily_ledger') }}">Daily Ledger</a>
                    @endcan
                    @can('reports.cash_transactions.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.cash_transactions') ? 'bg-gray-100 text-gray-900 font-semibold' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.cash_transactions') }}">Cash Transactions (Cash in Hand)</a>
                    @endcan
                    @can('reports.bank_transactions.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.bank_transactions') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.bank_transactions') }}">Bank Transactions</a>
                    @endcan
                    @can('reports.cheque_history.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.cheque_history') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.cheque_history') }}">Cheque History</a>
                    @endcan
                    @can('reports.student_due.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.student_due') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.student_due') }}">Student Due</a>
                    @endcan
                    @can('reports.student_due_aging.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.student_due_aging') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.student_due_aging') }}">Student Due Aging</a>
                    @endcan
                    @can('reports.student_top_due.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.student_top_due') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.student_top_due') }}">Top Due Students</a>
                    @endcan
                    @can('reports.teacher_epf.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.teacher_epf') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.teacher_epf') }}">Teacher EPF</a>
                    @endcan
                    @can('reports.company_epf.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.company_epf') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.company_epf') }}">Company EPF</a>
                    @endcan
                    @can('reports.teacher_etf.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.teacher_etf') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.teacher_etf') }}">Company ETF</a>
                    @endcan
                    @can('reports.epf_etf_totals.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.epf_etf_totals') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.epf_etf_totals') }}">EPF/ETF Totals</a>
                    @endcan
                    @can('reports.fee_collection_summary.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.fee_collection_summary') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.fee_collection_summary') }}">Fee Collection Summary</a>
                    @endcan
                    @can('reports.fee_collection_by_class.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.fee_collection_by_class') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.fee_collection_by_class') }}">Fee Collection by Class</a>
                    @endcan
                    @can('reports.fee_collection_by_category.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.fee_collection_by_category') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.fee_collection_by_category') }}">Fee Collection by Category</a>
                    @endcan
                    @can('reports.fee_collection_vs_expected.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.fee_collection_vs_expected') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.fee_collection_vs_expected') }}">Collected vs Expected</a>
                    @endcan
                    @can('reports.fee_discounts.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.fee_discounts') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.fee_discounts') }}">Discount/Waiver</a>
                    @endcan
                    @can('reports.fee_refunds.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.fee_refunds') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.fee_refunds') }}">Refund/Cancellation</a>
                    @endcan
                    @can('reports.seminars_collection.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.seminars_collection') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.seminars_collection') }}">Seminars Collection</a>
                    @endcan
                    @can('reports.extra_classes_collection.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.extra_classes_collection') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.extra_classes_collection') }}">Extra Classes Collection</a>
                    @endcan
                </div>
            </div>
        @endcan

        @if($canViewSettings || $canManageRoles || $canManageUsers)
            <div class="mt-2">
                <button type="button" @click="menus.settings = !menus.settings" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isSettings ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7z"/><path d="M19.4 15a1.7 1.7 0 0 0 .33 1.87l.06.06a2 2 0 0 1-1.42 3.41h-.2a2 2 0 0 1-1.41-.59l-.06-.06a1.7 1.7 0 0 0-1.87-.33 1.7 1.7 0 0 0-1 1.54V21a2 2 0 0 1-4 0v-.2a1.7 1.7 0 0 0-1-1.54 1.7 1.7 0 0 0-1.87.33l-.06.06A2 2 0 0 1 4.2 20.34H4a2 2 0 0 1-1.42-3.41l.06-.06A1.7 1.7 0 0 0 3 15.4a1.7 1.7 0 0 0-1.54-1H1a2 2 0 0 1 0-4h.2a1.7 1.7 0 0 0 1.54-1 1.7 1.7 0 0 0-.33-1.87l-.06-.06A2 2 0 0 1 3.8 4.66H4a2 2 0 0 1 1.41.59l.06.06A1.7 1.7 0 0 0 7.34 5a1.7 1.7 0 0 0 1-1.54V3a2 2 0 0 1 4 0v.2a1.7 1.7 0 0 0 1 1.54 1.7 1.7 0 0 0 1.87-.33l.06-.06A2 2 0 0 1 19.8 7.66l-.06.06A1.7 1.7 0 0 0 19.4 9.6a1.7 1.7 0 0 0 1.54 1H21a2 2 0 0 1 0 4h-.2a1.7 1.7 0 0 0-1.4 1z"/></svg>
                        <span>Settings</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="menus.settings ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="menus.settings" class="mt-1 space-y-1 pl-8" x-cloak>
                    @if($canViewSettingsGeneral)
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.general.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.general.edit') }}">School General</a>
                    @endif
                    @if($canViewSettingsStatus)
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.status.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.status.index') }}">System Status</a>
                    @endif
                    @if($canManageSettingsPromotion)
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.promotion.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.promotion.edit') }}">Promotion</a>
                    @endif
                    @if($canManageSettingsPrinter)
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.printer.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.printer.edit') }}">Printer</a>
                    @endif
                    @if($canManageSettingsSms)
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.sms.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.sms.edit') }}">SMS</a>
                    @endif
                    @if($canManageSettingsEmail)
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.email.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.email.edit') }}">Email (SMTP)</a>
                    @endif
                    @if($canManageSettingsSalaryComponents)
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.salary-components.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.salary-components.edit') }}">Salary Components</a>
                    @endif
                    @if($canManageSettingsBackups)
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.backups.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.backups.index') }}">Backups</a>
                    @endif
                    @if($canManageSettingsOpeningBalance)
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.opening-balance.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.opening-balance.edit') }}">Opening Balance</a>
                    @endif
                    @if($canManageUsers)
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('users.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('users.index') }}">Users</a>
                    @endif

                    @if($canManageRoles)
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('rbac.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('rbac.roles.index') }}">Roles & Permissions</a>
                    @endif
                </div>
            </div>
        @endif

        @if($canViewActivityLogs)
            <div class="mt-2">
                <a href="{{ route('audit_logs.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ request()->routeIs('audit_logs.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50' }}">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 4h18v4H3z"/><path d="M3 12h18v8H3z"/><path d="M7 12v8"/><path d="M12 12v8"/><path d="M17 12v8"/></svg>
                    <span>Activity Logs</span>
                </a>
            </div>
        @endif

        <div class="mt-4 pt-4 border-t border-gray-200">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                    <span>Logout</span>
                </button>
            </form>
        </div>
    </nav>
