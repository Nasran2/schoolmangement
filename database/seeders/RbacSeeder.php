<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Settings
            'settings.manage',

            // Class Rooms
            'classrooms.view',
            'classrooms.create',
            'classrooms.update',
            'classrooms.delete',

            // Revenue
            'revenue.add',
            'revenue.manage',
            'revenue.delete',
            'revenue.reports.download',
            'revenue.categories.manage',

            // Expense
            'expense.add',
            'expense.manage',
            'expense.delete',
            'expense.reports.download',
            'expense.categories.manage',

            // Students
            'students.add',
            'students.manage',
            'students.delete',
            'students.bulk_upload',
            'students.promote',
            'students.demote',
            'students.reports.download',

            // Teachers
            'teachers.add',
            'teachers.manage',
            'teachers.delete',
            'teachers.bulk_upload',
            'teachers.salary.pay',
            'teachers.reports.download',

            // SMS
            'sms.send.individual',
            'sms.send.bulk',
            'sms.send.selected_students',
            'sms.send.selected_grades',

            // Reports (global)
            'reports.view',
            'reports.download',

            // RBAC
            'roles.manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName);
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdminRole->syncPermissions(Permission::all());

        $user = User::query()->updateOrCreate(
            ['email' => 'admin@school.local'],
            [
                'name' => 'admin',
                'username' => 'admin',
                'password' => Hash::make('admin'),
            ]
        );

        if (! $user->hasRole($superAdminRole)) {
            $user->assignRole($superAdminRole);
        }
    }
}
