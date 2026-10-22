<?php

namespace App\Http\Controllers\Rbac;

use App\Http\Controllers\Controller;
use App\Services\AccessControl;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('rbac.roles.index', [
            'roles' => Role::query()->where('guard_name', 'web')->withCount(['users', 'permissions'])->orderBy('name')->get(),
        ]);
    }

    public function edit(Role $role): View
    {
        abort_unless($role->guard_name === 'web', 404);
        $permissions = Permission::query()->where('guard_name', 'web')->orderBy('name')->get();

        // Group permissions by category for easier management in the UI
        $groups = [
            'Dashboard' => ['dashboard.'],
            'Settings' => ['settings.'],
            'Users' => ['users.'],
            'RBAC' => ['roles.'],
            'Activity Logs' => ['audit_logs.'],
            'Students' => ['students.'],
            'Teachers' => ['teachers.'],
            'HR and Teacher Evaluations' => ['hr.', 'teacher_evaluations.'],
            'Seminars' => ['seminars.'],
            'Extra Classes' => ['extra_classes.'],
            'Visiting Teachers' => ['visiting_teachers.'],
            'Revenue' => ['revenue.'],
            'Expense' => ['expense.'],
            'Owner bank account' => ['owner_transfers.'],
            'Classrooms' => ['classrooms.'],
            'Class Fees' => ['class_fees.'],
            'Reports' => ['reports.'],
            'SMS' => ['sms.'],
        ];

        $permissionGroups = [];
        foreach ($permissions as $perm) {
            $placed = false;
            foreach ($groups as $label => $prefixes) {
                foreach ($prefixes as $prefix) {
                    if (str_starts_with($perm->name, $prefix)) {
                        $permissionGroups[$label][] = $perm;
                        $placed = true;
                        break 2;
                    }
                }
            }
            if (! $placed) {
                $permissionGroups['Other'][] = $perm;
            }
        }

        $labels = [
            'hr.directory.view' => 'Read Teacher and Student Details (No Financial Information)',
            'teacher_evaluations.view' => 'View Teacher Evaluations',
            'teacher_evaluations.manage' => 'Create and Edit Teacher Evaluations',
            'teacher_evaluations.export' => 'Download Evaluation Templates and Reports',
            'teacher_evaluations.delete' => 'Delete Teacher Evaluations',
            // Dashboard
            'dashboard.view' => 'Access Dashboard',
            'dashboard.widget.total_revenue.view' => 'Widget: Total Revenue',
            'dashboard.widget.total_expenses.view' => 'Widget: Total Expenses',
            'dashboard.widget.net_profit.view' => 'Widget: Net Profit',
            'dashboard.widget.cash_flow.view' => 'Widget: Cash Flow Chart',
            'dashboard.widget.revenue_vs_expense.view' => 'Widget: Revenue vs Expense',
            'dashboard.widget.due_students.view' => 'Widget: Due Students List',
            'dashboard.widget.revenue_category_breakdown.view' => 'Widget: Revenue Breakdown',
            'dashboard.widget.expense_category_breakdown.view' => 'Widget: Expense Breakdown',
            'dashboard.widget.upcoming_teacher_payments.view' => 'Widget: Upcoming Teacher Salaries',
            'dashboard.widget.enrollment_trend.view' => 'Widget: Enrollment Trend',
            'dashboard.widget.notifications.view' => 'Widget: Notifications',
            'dashboard.widget.recent_activity.view' => 'Widget: Recent Activity',
            'dashboard.widget.exam_fees.view' => 'Widget: Exam Fee Collections and Dues',
            'dashboard.widget.facility_fees.view' => 'Widget: Facility Fee Collections and Dues',

            // Settings
            'settings.manage' => 'Manage All Settings',
            'settings.general.manage' => 'Manage School General Settings',
            'settings.status.view' => 'View System Status',
            'settings.promotion.manage' => 'Manage Promotion Settings',
            'settings.printer.manage' => 'Manage Printer Settings',
            'settings.sms.manage' => 'Manage SMS Settings',
            'settings.email.manage' => 'Manage Email (SMTP) Settings',
            'settings.salary_components.manage' => 'Manage Salary Components Settings',
            'settings.backups.manage' => 'Manage Backups Settings',
            'settings.opening_balance.manage' => 'Set Opening Balance (One-time)',
            'settings.opening_balance.reset' => 'Reset Opening Balance',

            // Users
            'users.manage' => 'Create, Edit, Delete and Assign User Roles',
            'seminars.manage' => 'Manage Seminars, Attendance and Payments',
            'extra_classes.manage' => 'Manage Extra Classes, Enrollments and Payments',
            'visiting_teachers.manage' => 'Manage Visiting Teachers',

            // Classrooms
            'classrooms.view' => 'View Classrooms',
            'classrooms.create' => 'Create Classrooms',
            'classrooms.update' => 'Edit Classrooms',
            'classrooms.delete' => 'Delete Classrooms',
            'class_fees.view' => 'View Exam and Facility Fee Installments and Unpaid Students',

            // Revenue
            'revenue.add' => 'Add Revenue',
            'revenue.manage' => 'Manage Revenue Records',
            'revenue.delete' => 'Delete Revenue Records',
            'revenue.reports.download' => 'Download Revenue Reports',
            'revenue.categories.manage' => 'Manage Revenue Categories',

            // Expense
            'expense.add' => 'Add Expense',
            'expense.manage' => 'Manage Expense Records',
            'expense.delete' => 'Delete Expense Records',
            'expense.reports.download' => 'Download Expense Reports',
            'expense.categories.manage' => 'Manage Expense Categories',
            'owner_transfers.manage' => 'Record and View Transfers to Owner bank account',

            // Students
            'students.add' => 'Add Students',
            'students.manage' => 'Manage Students',
            'students.delete' => 'Delete Students',
            'students.bulk_upload' => 'Bulk Upload Students',
            'students.promote' => 'Promote Students',
            'students.demote' => 'Demote Students',
            'students.reports.download' => 'Download Student Reports',

            // Teachers
            'teachers.add' => 'Add Teachers',
            'teachers.manage' => 'Manage Teachers',
            'teachers.delete' => 'Delete Teachers',
            'teachers.bulk_upload' => 'Bulk Upload Teachers',
            'teachers.salary.pay' => 'Manage Salary Payments',
            'teachers.salary.summary.view' => 'View Salary Due & Upcoming',
            'teachers.salary.amounts.view' => 'View Teacher Salary Amounts',
            'teachers.salary.components' => 'Manage Salary Components',
            'teachers.reports.download' => 'Download Teacher Reports',

            // SMS
            'sms.send.individual' => 'Send Individual SMS',
            'sms.send.bulk' => 'Send Bulk SMS',
            'sms.send.selected_students' => 'Send to Selected Students',
            'sms.send.selected_grades' => 'Send to Selected Grades',

            // Reports
            'reports.view' => 'Access Reports Module',
            'reports.download' => 'Download Reports (Global)',
            'reports.revenue.view' => 'View Revenue Report',
            'reports.expense.view' => 'View Expense Report',
            'reports.outflows.view' => 'View All Outflows Report',
            'reports.financial.view' => 'View Financial Report',
            'reports.daily_ledger.view' => 'View Daily Ledger Report',
            'reports.cash_transactions.view' => 'View Cash Transactions Report',
            'reports.bank_transactions.view' => 'View Bank Transactions Report',
            'reports.cheque_history.view' => 'View Cheque History Report',
            'reports.student_due.view' => 'View Student Due Report',
            'reports.student_due_aging.view' => 'View Due Aging Report',
            'reports.student_top_due.view' => 'View Top Due Students',
            'reports.teacher_epf.view' => 'View Teacher EPF Report',
            'reports.teacher_etf.view' => 'View Company ETF Report',
            'reports.company_epf.view' => 'View Company EPF Report',
            'reports.epf_etf_totals.view' => 'View EPF/ETF Totals',
            'reports.fee_collection_summary.view' => 'View Fee Collection Summary',
            'reports.fee_collection_by_class.view' => 'View Collection by Class',
            'reports.fee_collection_by_category.view' => 'View Collection by Category',
            'reports.fee_collection_vs_expected.view' => 'View Collection vs Expected',
            'reports.fee_discounts.view' => 'View Fee Discounts Report',
            'reports.fee_refunds.view' => 'View Fee Refunds Report',
            'reports.seminars_collection.view' => 'View Seminars Collection',
            'reports.extra_classes_collection.view' => 'View Extra Classes Collection',

            // RBAC
            'roles.manage' => 'Manage Roles & Permissions',

            // Activity Logs
            'audit_logs.view' => 'View Activity Logs',
        ];

        return view('rbac.roles.edit', [
            'role' => $role,
            'permissions' => $permissions,
            'permissionGroups' => $permissionGroups,
            'permissionLabels' => $labels,
            'rolePermissions' => $role->permissions->pluck('name')->all(),
            'isSystemRole' => in_array($role->name, AccessControl::SYSTEM_ROLES, true),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeEditableRole($request, $role);
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role)],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['required', 'string', 'distinct', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);
        $this->validateName($validated['name'] ?? $role->name);
        $before = $role->permissions->pluck('name')->all();
        $next = $validated['permissions'] ?? [];
        foreach ($next as $permission) {
            if (! $request->user()->can($permission)) {
                throw ValidationException::withMessages(['permissions' => 'You can only grant permissions you hold yourself.']);
            }
        }

        DB::transaction(function () use ($role, $validated, $next, $before) {
            $role->update(['name' => $validated['name'] ?? $role->name]);
            $role->syncPermissions($next);
            app(AuditLogger::class)->log('rbac.role.permissions.update', $role, 'Role permissions updated', [
                'role' => $role->name,
                'added' => array_values(array_diff($next, $before)),
                'removed' => array_values(array_diff($before, $next)),
            ]);
        });

        return back()->with('status', 'Role updated.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('roles', 'name')->where('guard_name', 'web')],
        ]);
        $this->validateName($validated['name']);
        $role = DB::transaction(function () use ($validated) {
            $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
            app(AuditLogger::class)->log('rbac.role.create', $role, 'Role created', ['role' => $role->name]);

            return $role;
        });

        return redirect()->route('rbac.roles.edit', $role)->with('status', 'Role created. Choose its permissions below.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        $this->authorizeEditableRole($request, $role);
        DB::transaction(function () use ($role) {
            $role = Role::query()->lockForUpdate()->findOrFail($role->id);
            if ($role->users()->exists()) {
                throw ValidationException::withMessages(['role' => 'Reassign users before deleting this role.']);
            }
            app(AuditLogger::class)->log('rbac.role.delete', $role, 'Role deleted', ['role' => $role->name]);
            $role->delete();
        });

        return redirect()->route('rbac.roles.index')->with('status', 'Role deleted.');
    }

    private function authorizeEditableRole(Request $request, Role $role): void
    {
        abort_unless($role->guard_name === 'web', 404);
        abort_if(in_array($role->name, AccessControl::SYSTEM_ROLES, true), 403, 'System roles are protected. Create a custom role for restricted access.');
        abort_unless($role->permissions->every(fn ($permission) => $request->user()->can($permission->name)), 403);
    }

    private function validateName(string $name): void
    {
        foreach (AccessControl::SYSTEM_ROLES as $reserved) {
            if (strcasecmp(trim($name), $reserved) === 0) {
                throw ValidationException::withMessages(['name' => 'This name is reserved for a protected system role.']);
            }
        }
    }
}
