<?php

namespace Tests\Feature\Users;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('Super Admin', 'web'));

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Office Staff', 'username' => 'office', 'email' => 'office@example.test',
            'password' => 'secure-password', 'password_confirmation' => 'secure-password',
            'roles' => [], 'active' => '1',
        ], $overrides);
    }

    public function test_create_edit_multiple_roles_password_change_and_delete(): void
    {
        $admin = $this->administrator();
        $clerk = Role::findOrCreate('Clerk', 'web');
        $clerk->givePermissionTo('students.manage');
        $viewer = Role::findOrCreate('Viewer', 'web');
        $viewer->givePermissionTo('dashboard.view');
        $this->actingAs($admin)->get(route('users.create'))->assertOk();
        $this->post(route('users.store'), $this->payload(['roles' => ['Clerk', 'Viewer']]))->assertSessionHasNoErrors()->assertRedirect(route('users.index'));
        $target = User::where('username', 'office')->firstOrFail();
        $this->assertTrue(Hash::check('secure-password', $target->password));
        $this->assertTrue($target->hasAllRoles(['Clerk', 'Viewer']));
        $this->assertTrue($target->can('students.manage'));
        $this->get(route('users.edit', $target))->assertOk()->assertSee('Office Staff');
        $this->put(route('users.update', $target), $this->payload([
            'name' => 'Updated Staff', 'roles' => ['Viewer'], 'password' => '', 'password_confirmation' => '',
        ]))->assertSessionHasNoErrors();
        $target->refresh();
        $this->assertTrue(Hash::check('secure-password', $target->password));
        $this->assertFalse($target->can('students.manage'));
        $this->put(route('users.update', $target), $this->payload([
            'password' => 'changed-password', 'password_confirmation' => 'changed-password',
        ]))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('changed-password', $target->fresh()->password));
        $log = AuditLog::where('action', 'users.update')->latest('id')->firstOrFail();
        $this->assertSame($target->id, $log->auditable_id);
        $this->assertStringNotContainsString('changed-password', json_encode($log->metadata));
        $this->delete(route('users.destroy', $target))->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_password_and_role_validation_does_not_create_partial_accounts(): void
    {
        $this->actingAs($this->administrator())
            ->post(route('users.store'), $this->payload(['password' => 'a', 'password_confirmation' => 'a']))->assertSessionHasErrors('password');
        $this->post(route('users.store'), $this->payload(['roles' => ['Unknown']]))->assertSessionHasErrors('roles.0');
        $this->assertDatabaseMissing('users', ['username' => 'office']);
    }

    public function test_user_manager_cannot_escalate_privileges_or_modify_administrators(): void
    {
        $admin = $this->administrator();
        $developer = Role::findOrCreate('Developer', 'web');
        $manager = User::factory()->create();
        $manager->givePermissionTo('users.manage');
        $finance = Role::findOrCreate('Finance', 'web');
        $finance->givePermissionTo('revenue.manage');
        $this->actingAs($manager)->get(route('users.create'))->assertDontSee('Super Admin')->assertDontSee('Finance');
        foreach (['Super Admin', $developer->name, 'Finance'] as $role) {
            $this->post(route('users.store'), $this->payload(['roles' => [$role]]))->assertSessionHasErrors('roles');
        }
        $this->get(route('users.edit', $admin))->assertForbidden();
        $this->post(route('users.status', $admin), ['active' => '0'])->assertForbidden();
        $this->delete(route('users.destroy', $admin))->assertForbidden();
        $this->assertDatabaseMissing('users', ['username' => 'office']);
    }

    public function test_users_cannot_remove_own_access_or_delete_themselves(): void
    {
        $admin = $this->administrator();
        $this->actingAs($admin)->get(route('users.edit', $admin))->assertOk()->assertSee('Super Admin');
        $this->post(route('users.status', $admin), ['active' => '0'])->assertSessionHasErrors('status');
        $this->put(route('users.update', $admin), $this->payload([
            'username' => $admin->username, 'email' => $admin->email, 'roles' => [],
        ]))->assertSessionHasErrors('roles');
        $this->delete(route('users.destroy', $admin))->assertSessionHasErrors('status');
        $this->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasErrors('status');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'active' => true]);
    }

    public function test_deactivation_accepts_numeric_boolean_and_blocks_existing_sessions(): void
    {
        $admin = $this->administrator();
        $target = User::factory()->create();
        $this->actingAs($admin)->post(route('users.status', $target), ['active' => 0])->assertSessionHasNoErrors();
        $this->assertFalse($target->fresh()->active);
        $this->actingAs($target->fresh())->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->actingAs($admin)->post(route('users.status', $target), ['active' => 1])->assertSessionHasNoErrors();
        $this->assertTrue($target->fresh()->active);
    }

    public function test_login_lands_on_an_authorized_module_without_dashboard_permission(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('users.manage');
        $this->withSession(['url.intended' => route('dashboard')])
            ->post(route('login'), ['username' => $user->username, 'password' => 'password'])
            ->assertRedirect(route('users.index', absolute: false));
        $this->get('/')->assertRedirect(route('users.index'));
        $this->get(route('users.index'))->assertOk();
    }

    public function test_global_card_totals_remain_correct_across_pages_and_filters(): void
    {
        $admin = $this->administrator();
        User::factory()->count(29)->create(['active' => true]);
        User::factory()->count(2)->create(['active' => false]);
        $developer = User::factory()->create();
        $developer->assignRole(Role::findOrCreate('Developer', 'web'));
        $this->actingAs($admin)->get(route('users.index', ['page' => 2]))->assertOk()->assertViewHas('stats', ['total' => 32, 'active' => 30]);
        $this->get(route('users.index', ['active' => '0']))->assertOk()->assertViewHas('users', fn ($users) => $users->total() === 2);
        $this->get(route('users.index', ['q' => $admin->username]))->assertOk()->assertSee($admin->email);
    }
}
