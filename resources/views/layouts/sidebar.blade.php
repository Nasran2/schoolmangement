@php
    $user = Auth::user();
    $roleLabel = method_exists($user, 'getRoleNames') ? ($user->getRoleNames()->first() ?? 'User') : 'User';

    $isDashboard = request()->routeIs('dashboard');
    $isRevenue = request()->routeIs('revenue.*');
    $isExpense = request()->routeIs('expense.*');
    $isStudents = request()->routeIs('students.*');
    $isTeachers = request()->routeIs('teachers.*') || request()->routeIs('teacher-salary-payments.*');
    $isReports = request()->routeIs('reports.*');
    $isClassrooms = request()->routeIs('classrooms.*');
    $isSettings = request()->routeIs('settings.*') || request()->routeIs('rbac.*');
@endphp

<aside class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 hidden lg:flex lg:flex-col">
    <div class="px-5 py-5 border-b border-gray-200">
        <div class="flex items-center gap-3">
            <div class="relative inline-flex items-center justify-center">
                <span class="absolute inline-flex h-full w-full rounded-full bg-gray-200 opacity-60 motion-safe:animate-ping"></span>
                <x-application-logo class="relative h-10 w-10 fill-current text-gray-700" />
            </div>
            <div class="min-w-0">
                <div class="text-sm font-semibold text-gray-900 truncate">
                    {{ $schoolName ?? config('app.name') }}
                </div>
                <div class="text-xs text-gray-600 truncate">
                    {{ $roleLabel }} · {{ $user?->name }}
                </div>
            </div>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4" x-data="{ open: { revenue: @js($isRevenue), expense: @js($isExpense), students: @js($isStudents), teachers: @js($isTeachers), reports: @js($isReports), classrooms: @js($isClassrooms), settings: @js($isSettings) } }">
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ $isDashboard ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12l9-9 9 9"/><path d="M9 21V9h6v12"/></svg>
            <span>Dashboard</span>
        </a>

        @canany(['revenue.add','revenue.manage','revenue.categories.manage'])
            <div class="mt-3">
                <button type="button" @click="open.revenue = !open.revenue" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isRevenue ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22"/><path d="M17 5H9a3 3 0 0 0 0 6h6a3 3 0 0 1 0 6H6"/></svg>
                        <span>Revenue</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="open.revenue ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="open.revenue" class="mt-1 space-y-1 pl-8" x-cloak>
                    @can('revenue.add')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('revenue.items.create') && !request()->has('quick') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('revenue.items.create') }}">Add</a>
                        <a class="block px-3 py-2 rounded-md text-sm {{ (request()->routeIs('revenue.items.create') && request()->get('quick')==='monthly') ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200' : 'text-indigo-700 hover:bg-indigo-50' }}" href="{{ route('revenue.items.create', ['quick' => 'monthly']) }}">Quick Monthly Payment</a>
                    @endcan
                    @can('revenue.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('revenue.items.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('revenue.items.index') }}">Manage</a>
                    @endcan
                    @can('revenue.categories.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('revenue.categories.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('revenue.categories.index') }}">Categories</a>
                    @endcan
                </div>
            </div>
        @endcanany

        @canany(['expense.add','expense.manage','expense.categories.manage'])
            <div class="mt-2">
                <button type="button" @click="open.expense = !open.expense" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isExpense ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8c-3.314 0-6 1.343-6 3s2.686 3 6 3 6-1.343 6-3-2.686-3-6-3z"/><path d="M6 11v6c0 1.657 2.686 3 6 3s6-1.343 6-3v-6"/><path d="M6 17c0 1.657 2.686 3 6 3s6-1.343 6-3"/></svg>
                        <span>Expense</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="open.expense ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="open.expense" class="mt-1 space-y-1 pl-8" x-cloak>
                    @can('expense.add')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('expense.items.create') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('expense.items.create') }}">Add</a>
                    @endcan
                    @can('expense.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('expense.items.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('expense.items.index') }}">Manage</a>
                    @endcan
                    @can('expense.categories.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('expense.categories.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('expense.categories.index') }}">Categories</a>
                    @endcan
                    @can('reports.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.financial') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.financial') }}">Revenue vs Expense</a>
                    @endcan
                </div>
            </div>
        @endcanany

        @canany(['students.add','students.manage'])
            <div class="mt-2">
                <button type="button" @click="open.students = !open.students" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isStudents ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 11c1.657 0 3-1.343 3-3S17.657 5 16 5s-3 1.343-3 3 1.343 3 3 3z"/><path d="M8 11c1.657 0 3-1.343 3-3S9.657 5 8 5 5 6.343 5 8s1.343 3 3 3z"/><path d="M8 13c-2.761 0-5 2.239-5 5"/><path d="M16 13c-2.761 0-5 2.239-5 5"/></svg>
                        <span>Students</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="open.students ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="open.students" class="mt-1 space-y-1 pl-8" x-cloak>
                    @can('students.add')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('students.create') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('students.create') }}">Add</a>
                    @endcan
                    @can('students.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('students.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('students.index') }}">Manage</a>
                    @endcan
                    @can('students.bulk_upload')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('students.bulk.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('students.bulk.create') }}">Bulk Upload</a>
                    @endcan
                </div>
            </div>
        @endcanany

        @canany(['classrooms.view','classrooms.create','classrooms.update','classrooms.delete'])
            <div class="mt-2">
                <button type="button" @click="open.classrooms = !open.classrooms" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isClassrooms ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"/><path d="M4 10h16"/><path d="M4 14h16"/><path d="M4 18h16"/></svg>
                        <span>Class Rooms</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="open.classrooms ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="open.classrooms" class="mt-1 space-y-1 pl-8" x-cloak>
                    @can('classrooms.create')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('classrooms.create') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('classrooms.create') }}">Add</a>
                    @endcan
                    @can('classrooms.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ (request()->routeIs('classrooms.index') || request()->routeIs('classrooms.edit')) ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('classrooms.index') }}">Manage</a>
                    @endcan
                </div>
            </div>
        @endcanany

        @canany(['teachers.add','teachers.manage'])
            <div class="mt-2">
                <button type="button" @click="open.teachers = !open.teachers" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isTeachers ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 12c2.21 0 4-1.79 4-4S14.21 4 12 4 8 5.79 8 8s1.79 4 4 4z"/><path d="M4 20c0-4.418 3.582-8 8-8s8 3.582 8 8"/></svg>
                        <span>Teachers</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="open.teachers ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="open.teachers" class="mt-1 space-y-1 pl-8" x-cloak>
                    @can('teachers.add')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('teachers.create') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('teachers.create') }}">Add</a>
                    @endcan
                    @can('teachers.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('teachers.index') || request()->routeIs('teachers.show') || request()->routeIs('teachers.edit') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('teachers.index') }}">Manage</a>
                    @endcan
                    @can('teachers.salary.pay')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('teacher-salary-payments.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('teacher-salary-payments.index') }}">Salary Payments</a>
                    @endcan
                    @can('teachers.bulk_upload')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('teachers.bulk.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('teachers.bulk.create') }}">Bulk Upload</a>
                    @endcan
                </div>
            </div>
        @endcanany

        @can('reports.view')
            <div class="mt-2">
                <button type="button" @click="open.reports = !open.reports" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isReports ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 13h3v6H7z"/><path d="M12 9h3v10h-3z"/><path d="M17 5h3v14h-3z"/></svg>
                        <span>Reports</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="open.reports ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="open.reports" class="mt-1 space-y-1 pl-8" x-cloak>
                    <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.revenue') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.revenue') }}">Revenue</a>
                    <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.expense') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.expense') }}">Expense</a>
                    <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.financial') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.financial') }}">Financial</a>
                    <span class="block px-3 py-2 rounded-md text-sm text-gray-400">Student</span>
                    <span class="block px-3 py-2 rounded-md text-sm text-gray-400">Teacher</span>
                </div>
            </div>
        @endcan

        @canany(['settings.manage','roles.manage'])
            <div class="mt-2">
                <button type="button" @click="open.settings = !open.settings" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isSettings ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7z"/><path d="M19.4 15a1.7 1.7 0 0 0 .33 1.87l.06.06a2 2 0 0 1-1.42 3.41h-.2a2 2 0 0 1-1.41-.59l-.06-.06a1.7 1.7 0 0 0-1.87-.33 1.7 1.7 0 0 0-1 1.54V21a2 2 0 0 1-4 0v-.2a1.7 1.7 0 0 0-1-1.54 1.7 1.7 0 0 0-1.87.33l-.06.06A2 2 0 0 1 4.2 20.34H4a2 2 0 0 1-1.42-3.41l.06-.06A1.7 1.7 0 0 0 3 15.4a1.7 1.7 0 0 0-1.54-1H1a2 2 0 0 1 0-4h.2a1.7 1.7 0 0 0 1.54-1 1.7 1.7 0 0 0-.33-1.87l-.06-.06A2 2 0 0 1 3.8 4.66H4a2 2 0 0 1 1.41.59l.06.06A1.7 1.7 0 0 0 7.34 5a1.7 1.7 0 0 0 1-1.54V3a2 2 0 0 1 4 0v.2a1.7 1.7 0 0 0 1 1.54 1.7 1.7 0 0 0 1.87-.33l.06-.06A2 2 0 0 1 19.8 7.66l-.06.06A1.7 1.7 0 0 0 19.4 9.6a1.7 1.7 0 0 0 1.54 1H21a2 2 0 0 1 0 4h-.2a1.7 1.7 0 0 0-1.4 1z"/></svg>
                        <span>Settings</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="open.settings ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="open.settings" class="mt-1 space-y-1 pl-8" x-cloak>
                    @can('settings.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.general.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.general.edit') }}">School General</a>
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.promotion.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.promotion.edit') }}">Promotion</a>
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.printer.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.printer.edit') }}">Printer</a>
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.sms.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.sms.edit') }}">SMS</a>
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.salary-components.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.salary-components.edit') }}">Salary Components</a>
                    @endcan
                    @can('roles.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('rbac.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('rbac.roles.index') }}">Roles & Permissions</a>
                    @endcan
                </div>
            </div>
        @endcanany

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
</aside>

<!-- Mobile sidebar -->
<div x-data="{ open: false }" class="lg:hidden">
    <div class="fixed top-0 left-0 right-0 z-40 bg-white border-b border-gray-200">
        <div class="h-14 px-4 flex items-center justify-between">
            <button type="button" @click="open = true" class="inline-flex items-center justify-center p-2 rounded-md text-gray-600 hover:bg-gray-100">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg>
            </button>
            <div class="text-sm font-semibold text-gray-900 truncate">{{ $schoolName ?? config('app.name') }}</div>
            <div class="w-10"></div>
        </div>
    </div>

    <div x-show="open" class="fixed inset-0 z-50" x-cloak>
        <div class="absolute inset-0 bg-black/30" @click="open = false"></div>
        <div class="absolute inset-y-0 left-0 w-64 bg-white border-r border-gray-200 overflow-y-auto">
            <div class="px-5 py-5 border-b border-gray-200 flex items-center justify-between">
                <div class="text-sm font-semibold text-gray-900 truncate">{{ $schoolName ?? config('app.name') }}</div>
                <button type="button" @click="open = false" class="p-2 rounded-md text-gray-600 hover:bg-gray-100">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 18L18 6"/><path d="M6 6l12 12"/></svg>
                </button>
            </div>
    <nav class="flex-1 overflow-y-auto px-3 py-4" x-data="{ menus: { revenue: @js($isRevenue), expense: @js($isExpense), students: @js($isStudents), teachers: @js($isTeachers), reports: @js($isReports), classrooms: @js($isClassrooms), settings: @js($isSettings) } }">
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium {{ $isDashboard ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12l9-9 9 9"/><path d="M9 21V9h6v12"/></svg>
            <span>Dashboard</span>
        </a>

        @canany(['revenue.add','revenue.manage','revenue.categories.manage'])
            <div class="mt-3">
                <button type="button" @click="menus.revenue = !menus.revenue" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isRevenue ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22"/><path d="M17 5H9a3 3 0 0 0 0 6h6a3 3 0 0 1 0 6H6"/></svg>
                        <span>Revenue</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="menus.revenue ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="menus.revenue" class="mt-1 space-y-1 pl-8" x-cloak>
                    @can('revenue.add')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('revenue.items.create') && !request()->has('quick') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('revenue.items.create') }}">Add</a>
                        <a class="block px-3 py-2 rounded-md text-sm {{ (request()->routeIs('revenue.items.create') && request()->get('quick')==='monthly') ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200' : 'text-indigo-700 hover:bg-indigo-50' }}" href="{{ route('revenue.items.create', ['quick' => 'monthly']) }}">Quick Monthly Payment</a>
                    @endcan
                    @can('revenue.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('revenue.items.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('revenue.items.index') }}">Manage</a>
                    @endcan
                    @can('revenue.categories.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('revenue.categories.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('revenue.categories.index') }}">Categories</a>
                    @endcan
                </div>
            </div>
        @endcanany

        @canany(['expense.add','expense.manage','expense.categories.manage'])
            <div class="mt-2">
                <button type="button" @click="menus.expense = !menus.expense" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isExpense ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8c-3.314 0-6 1.343-6 3s2.686 3 6 3 6-1.343 6-3-2.686-3-6-3z"/><path d="M6 11v6c0 1.657 2.686 3 6 3s6-1.343 6-3v-6"/><path d="M6 17c0 1.657 2.686 3 6 3s6-1.343 6-3"/></svg>
                        <span>Expense</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="menus.expense ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="menus.expense" class="mt-1 space-y-1 pl-8" x-cloak>
                    @can('expense.add')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('expense.items.create') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('expense.items.create') }}">Add</a>
                    @endcan
                    @can('expense.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('expense.items.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('expense.items.index') }}">Manage</a>
                    @endcan
                    @can('expense.categories.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('expense.categories.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('expense.categories.index') }}">Categories</a>
                    @endcan
                    @can('reports.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.financial') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.financial') }}">Revenue vs Expense</a>
                    @endcan
                </div>
            </div>
        @endcanany

        @canany(['students.add','students.manage'])
            <div class="mt-2">
                <button type="button" @click="menus.students = !menus.students" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isStudents ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 11c1.657 0 3-1.343 3-3S17.657 5 16 5s-3 1.343-3 3 1.343 3 3 3z"/><path d="M8 11c1.657 0 3-1.343 3-3S9.657 5 8 5 5 6.343 5 8s1.343 3 3 3z"/><path d="M8 13c-2.761 0-5 2.239-5 5"/><path d="M16 13c-2.761 0-5 2.239-5 5"/></svg>
                        <span>Students</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="menus.students ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="menus.students" class="mt-1 space-y-1 pl-8" x-cloak>
                    @can('students.add')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('students.create') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('students.create') }}">Add</a>
                    @endcan
                    @can('students.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('students.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('students.index') }}">Manage</a>
                    @endcan
                    @can('students.bulk_upload')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('students.bulk.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('students.bulk.create') }}">Bulk Upload</a>
                    @endcan
                </div>
            </div>
        @endcanany

        @canany(['classrooms.view','classrooms.create','classrooms.update','classrooms.delete'])
            <div class="mt-2">
                <button type="button" @click="menus.classrooms = !menus.classrooms" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isClassrooms ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16"/><path d="M4 10h16"/><path d="M4 14h16"/><path d="M4 18h16"/></svg>
                        <span>Class Rooms</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="menus.classrooms ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="menus.classrooms" class="mt-1 space-y-1 pl-8" x-cloak>
                    @can('classrooms.create')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('classrooms.create') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('classrooms.create') }}">Add</a>
                    @endcan
                    @can('classrooms.view')
                        <a class="block px-3 py-2 rounded-md text-sm {{ (request()->routeIs('classrooms.index') || request()->routeIs('classrooms.edit')) ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('classrooms.index') }}">Manage</a>
                    @endcan
                </div>
            </div>
        @endcanany

        @canany(['teachers.add','teachers.manage'])
            <div class="mt-2">
                <button type="button" @click="menus.teachers = !menus.teachers" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isTeachers ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 12c2.21 0 4-1.79 4-4S14.21 4 12 4 8 5.79 8 8s1.79 4 4 4z"/><path d="M4 20c0-4.418 3.582-8 8-8s8 3.582 8 8"/></svg>
                        <span>Teachers</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="menus.teachers ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="menus.teachers" class="mt-1 space-y-1 pl-8" x-cloak>
                    @can('teachers.add')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('teachers.create') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('teachers.create') }}">Add</a>
                    @endcan
                    @can('teachers.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('teachers.index') || request()->routeIs('teachers.show') || request()->routeIs('teachers.edit') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('teachers.index') }}">Manage</a>
                    @endcan
                    @can('teachers.salary.pay')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('teacher-salary-payments.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('teacher-salary-payments.index') }}">Salary Payments</a>
                    @endcan
                    @can('teachers.bulk_upload')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('teachers.bulk.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('teachers.bulk.create') }}">Bulk Upload</a>
                    @endcan
                </div>
            </div>
        @endcanany

        @can('reports.view')
            <div class="mt-2">
                <button type="button" @click="menus.reports = !menus.reports" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isReports ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M7 13h3v6H7z"/><path d="M12 9h3v10h-3z"/><path d="M17 5h3v14h-3z"/></svg>
                        <span>Reports</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="menus.reports ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="menus.reports" class="mt-1 space-y-1 pl-8" x-cloak>
                    <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.revenue') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.revenue') }}">Revenue</a>
                    <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.expense') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.expense') }}">Expense</a>
                    <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('reports.financial') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('reports.financial') }}">Financial</a>
                    <span class="block px-3 py-2 rounded-md text-sm text-gray-400">Student</span>
                    <span class="block px-3 py-2 rounded-md text-sm text-gray-400">Teacher</span>
                </div>
            </div>
        @endcan

        @canany(['settings.manage','roles.manage'])
            <div class="mt-2">
                <button type="button" @click="menus.settings = !menus.settings" class="w-full flex items-center justify-between px-3 py-2 rounded-md text-sm font-medium {{ $isSettings ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}">
                    <span class="flex items-center gap-3">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15.5A3.5 3.5 0 1 0 12 8.5a3.5 3.5 0 0 0 0 7z"/><path d="M19.4 15a1.7 1.7 0 0 0 .33 1.87l.06.06a2 2 0 0 1-1.42 3.41h-.2a2 2 0 0 1-1.41-.59l-.06-.06a1.7 1.7 0 0 0-1.87-.33 1.7 1.7 0 0 0-1 1.54V21a2 2 0 0 1-4 0v-.2a1.7 1.7 0 0 0-1-1.54 1.7 1.7 0 0 0-1.87.33l-.06.06A2 2 0 0 1 4.2 20.34H4a2 2 0 0 1-1.42-3.41l.06-.06A1.7 1.7 0 0 0 3 15.4a1.7 1.7 0 0 0-1.54-1H1a2 2 0 0 1 0-4h.2a1.7 1.7 0 0 0 1.54-1 1.7 1.7 0 0 0-.33-1.87l-.06-.06A2 2 0 0 1 3.8 4.66H4a2 2 0 0 1 1.41.59l.06.06A1.7 1.7 0 0 0 7.34 5a1.7 1.7 0 0 0 1-1.54V3a2 2 0 0 1 4 0v.2a1.7 1.7 0 0 0 1 1.54 1.7 1.7 0 0 0 1.87-.33l.06-.06A2 2 0 0 1 19.8 7.66l-.06.06A1.7 1.7 0 0 0 19.4 9.6a1.7 1.7 0 0 0 1.54 1H21a2 2 0 0 1 0 4h-.2a1.7 1.7 0 0 0-1.4 1z"/></svg>
                        <span>Settings</span>
                    </span>
                    <svg class="h-4 w-4 transition" :class="menus.settings ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
                <div x-show="menus.settings" class="mt-1 space-y-1 pl-8" x-cloak>
                    @can('settings.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.general.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.general.edit') }}">School General</a>
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.promotion.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.promotion.edit') }}">Promotion</a>
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.printer.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.printer.edit') }}">Printer</a>
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.sms.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.sms.edit') }}">SMS</a>
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('settings.salary-components.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('settings.salary-components.edit') }}">Salary Components</a>
                    @endcan
                    @can('roles.manage')
                        <a class="block px-3 py-2 rounded-md text-sm {{ request()->routeIs('rbac.*') ? 'bg-gray-100 text-gray-900' : 'text-gray-700 hover:bg-gray-50' }}" href="{{ route('rbac.roles.index') }}">Roles & Permissions</a>
                    @endcan
                </div>
            </div>
        @endcanany

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
        </div>
    </div>
</div>
