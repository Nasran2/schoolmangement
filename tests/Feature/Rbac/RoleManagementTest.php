<?php

namespace Tests\Feature\Rbac;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Super Admin', 'web'));

        return $user;
    }

    public function test_role_creation_rename_permission_updates_and_deletion(): void
    {
        $this->actingAs($this->administrator())->post(route('rbac.roles.store'), ['name' => 'Office'])->assertSessionHasNoErrors();
        $role = Role::where('name', 'Office')->firstOrFail();
        $this->get(route('rbac.roles.index'))->assertOk();
        $this->get(route('rbac.roles.edit', $role))->assertOk()->assertSee('Select group');
        $this->put(route('rbac.roles.update', $role), ['name' => 'Accounts', 'permissions' => ['dashboard.view', 'dashboard.widget.total_revenue.view', 'seminars.manage']])->assertSessionHasNoErrors();
        $this->assertTrue($role->fresh()->hasPermissionTo('seminars.manage'));
        $this->assertSame('Accounts', $role->fresh()->name);
        $this->put(route('rbac.roles.update', $role), [])->assertSessionHasNoErrors();
        $this->assertCount(0, $role->fresh()->permissions);
        $this->delete(route('rbac.roles.destroy', $role))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_assigned_roles_and_system_roles_cannot_be_deleted(): void
    {
        $admin = $this->administrator();
        $role = Role::findOrCreate('Staff', 'web');
        User::factory()->create()->assignRole($role);
        $this->actingAs($admin)->delete(route('rbac.roles.destroy', $role))->assertSessionHasErrors('role');
        foreach (['Super Admin', 'Admin', 'Developer'] as $name) {
            $system = Role::findOrCreate($name, 'web');
            $this->get(route('rbac.roles.edit', $system))->assertOk()->assertSee('This system role is protected');
            $this->put(route('rbac.roles.update', $system), [])->assertForbidden();
            $this->delete(route('rbac.roles.destroy', $system))->assertForbidden();
        }
    }

    public function test_invalid_permissions_and_reserved_names_return_validation_errors(): void
    {
        $role = Role::findOrCreate('Staff', 'web');
        $this->actingAs($this->administrator())->put(route('rbac.roles.update', $role), ['permissions' => ['not.a.permission']])->assertSessionHasErrors('permissions.0');
        $this->post(route('rbac.roles.store'), ['name' => 'developer'])->assertSessionHasErrors('name');
        $this->put(route('rbac.roles.update', $role), ['name' => 'ADMIN'])->assertSessionHasErrors('name');
    }

    public function test_delegated_role_manager_cannot_grant_permissions_they_do_not_hold(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo('roles.manage');
        $role = Role::findOrCreate('Staff', 'web');
        $this->actingAs($manager)->put(route('rbac.roles.update', $role), ['permissions' => ['users.manage']])->assertSessionHasErrors('permissions');
        $role->givePermissionTo('revenue.manage');
        $this->put(route('rbac.roles.update', $role), [])->assertForbidden();
        $this->delete(route('rbac.roles.destroy', $role))->assertForbidden();
    }

    public function test_permission_changes_take_effect_for_assigned_users(): void
    {
        $admin = $this->administrator();
        $role = Role::findOrCreate('Office', 'web');
        $target = User::factory()->create();
        $target->assignRole($role);
        $this->actingAs($target)->get(route('students.index'))->assertForbidden();
        $this->actingAs($admin)->put(route('rbac.roles.update', $role), ['permissions' => ['students.manage']])->assertSessionHasNoErrors();
        $this->actingAs($target->fresh())->get(route('students.index'))->assertOk();
        $this->actingAs($admin)->put(route('rbac.roles.update', $role), [])->assertSessionHasNoErrors();
        $this->actingAs($target->fresh())->get(route('students.index'))->assertForbidden();
    }
}
