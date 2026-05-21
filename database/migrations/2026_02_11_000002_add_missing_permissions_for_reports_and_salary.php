<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $names = [
            // Reports
            'reports.teacher_epf.view',
            'reports.teacher_etf.view',
            'reports.company_epf.view',
            'reports.epf_etf_totals.view',

            // Dashboard widgets
            'dashboard.widget.upcoming_teacher_payments.view',

            // Teachers
            'teachers.salary.amounts.view',

            // Users
            'users.manage',
        ];

        foreach ($names as $name) {
            if (! Permission::query()->where('name', $name)->exists()) {
                Permission::create(['name' => $name]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $names = [
            'reports.teacher_epf.view',
            'reports.teacher_etf.view',
            'reports.company_epf.view',
            'reports.epf_etf_totals.view',
            'dashboard.widget.upcoming_teacher_payments.view',
            'teachers.salary.amounts.view',
            'users.manage',
        ];

        Permission::query()->whereIn('name', $names)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
