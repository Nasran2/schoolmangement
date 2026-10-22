@php
    $user = Auth::user();
    $roleLabel = method_exists($user, 'getRoleNames') ? ($user->getRoleNames()->first() ?? 'User') : 'User';

    $isDashboard = request()->routeIs('dashboard');
    $isRevenue = request()->routeIs('revenue.*');
    $isExpense = request()->routeIs('expense.*');
    $isStudents = request()->routeIs('students.*', 'hr.students.*');
    $isTeachers = request()->routeIs('teachers.*', 'teacher-salary-payments.*', 'hr.teachers.*');
    $isEvaluations = request()->routeIs('teacher-evaluations.*');
    $canReadDirectory = $user?->can('hr.directory.view') ?? false;
    $canViewEvaluations = $user?->can('teacher_evaluations.view') ?? false;
    $isReports = request()->routeIs('reports.*');
    $isClassrooms = request()->routeIs('classrooms.*');
    $isSettings = request()->routeIs('settings.*') || request()->routeIs('rbac.*') || request()->routeIs('users.*');
    $isDeveloper = request()->routeIs('developer.*');
    $isDeveloperStudents = request()->routeIs('developer.students');
    $isDeveloperTeachers = request()->routeIs('developer.teachers');
    $isDeveloperUsers = request()->routeIs('developer.users');

    // Match menu visibility to the same authorization checks used by routes.
    $canViewDashboard = $user?->can('dashboard.view') ?? false;
    $canManageRevenue = $user?->can('revenue.manage') ?? false;
    $canAddRevenue = $user?->can('revenue.add') ?? false;
    $canManageRevenueCategories = $user?->can('revenue.categories.manage') ?? false;
    $canManageExpense = $user?->can('expense.manage') ?? false;
    $canAddExpense = $user?->can('expense.add') ?? false;
    $canManageExpenseCategories = $user?->can('expense.categories.manage') ?? false;
    $canManageStudents = $user?->can('students.manage') ?? false;
    $canAddStudents = $user?->can('students.add') ?? false;
    $canBulkUploadStudents = $user?->can('students.bulk_upload') ?? false;
    $canManageClassrooms = ($user?->can('classrooms.view') ?? false) || ($user?->can('classrooms.create') ?? false) || ($user?->can('classrooms.update') ?? false) || ($user?->can('classrooms.delete') ?? false);
    $canManageTeachers = $user?->can('teachers.manage') ?? false;
    $canAddTeachers = $user?->can('teachers.add') ?? false;
    $canViewReports = $user?->can('reports.view') ?? false;
    $canManageUsers = $user?->can('users.manage') ?? false;
    $canManageRoles = $user?->can('roles.manage') ?? false;
    $canViewActivityLogs = $user?->can('audit_logs.view') ?? false;
    $canViewSettingsGeneral = $user?->canAny(['settings.manage', 'settings.general.manage']) ?? false;
    $canViewSettingsStatus = $user?->canAny(['settings.manage', 'settings.status.view']) ?? false;
    $canManageSettingsPromotion = $user?->canAny(['settings.manage', 'settings.promotion.manage']) ?? false;
    $canManageSettingsPrinter = $user?->canAny(['settings.manage', 'settings.printer.manage']) ?? false;
    $canManageSettingsSms = $user?->canAny(['settings.manage', 'settings.sms.manage']) ?? false;
    $canManageSettingsEmail = $user?->canAny(['settings.manage', 'settings.email.manage']) ?? false;
    $canManageSettingsSalaryComponents = $user?->canAny(['settings.manage', 'settings.salary_components.manage']) ?? false;
    $canManageSettingsBackups = $user?->canAny(['settings.manage', 'settings.backups.manage']) ?? false;
    $canManageSettingsOpeningBalance = $user?->canAny(['settings.manage', 'settings.opening_balance.manage']) ?? false;
    $canViewSettings = ($user?->can('settings.manage') ?? false) || $canViewSettingsGeneral || $canViewSettingsStatus || $canManageSettingsPromotion || $canManageSettingsPrinter || $canManageSettingsSms || $canManageSettingsEmail || $canManageSettingsSalaryComponents || $canManageSettingsBackups || $canManageSettingsOpeningBalance;
@endphp

<aside class="fixed inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 hidden lg:flex lg:flex-col">
    <div class="px-5 py-5 border-b border-gray-200">
        <div class="flex items-center gap-3">
            <div class="relative inline-flex items-center justify-center">
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

    @include('layouts.sidebar-menu')
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
    @include('layouts.sidebar-menu')
        </div>
    </div>
</div>
