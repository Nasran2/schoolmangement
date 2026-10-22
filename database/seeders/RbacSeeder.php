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
        $permissions = config('access', []);

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName);
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdminRole->syncPermissions(Permission::all());

        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $adminRole->syncPermissions(Permission::all());

        Role::findOrCreate('HR admin', 'web')->syncPermissions(\App\Services\TeacherEvaluations\EvaluationForm::HR_PERMISSIONS);

        $developerRole = Role::firstOrCreate(['name' => 'Developer']);
        $developerRole->syncPermissions([]);

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

        $developer = User::query()->updateOrCreate(
            ['username' => 'developer'],
            [
                'name' => '',
                'email' => 'developer@school.local',
                'password' => Hash::make('password'),
            ]
        );

        $developer->syncRoles([$developerRole]);
    }
}
