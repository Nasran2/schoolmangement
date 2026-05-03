<?php

namespace Tests\Feature\Authorization;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ModuleAccessHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_role_cannot_access_hardened_module_routes(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('seminars.index'))->assertForbidden();
        $this->actingAs($user)->get(route('extra-classes.index'))->assertForbidden();
        $this->actingAs($user)->get(route('visiting-teachers.index'))->assertForbidden();
        $this->actingAs($user)->get(route('teacher-lookup', ['q' => 'abc']))->assertForbidden();
    }

    public function test_admin_role_can_access_hardened_module_routes(): void
    {
        $adminRole = Role::findOrCreate('Admin');

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole($adminRole);

        $this->actingAs($user)->get(route('seminars.index'))->assertOk();
        $this->actingAs($user)->get(route('extra-classes.index'))->assertOk();
        $this->actingAs($user)->get(route('visiting-teachers.index'))->assertOk();
        $this->actingAs($user)->get(route('teacher-lookup', ['q' => 'abc']))->assertOk();
    }

    public function test_teacher_lookup_does_not_leak_inactive_teachers_via_phone_normalization(): void
    {
        $adminRole = Role::findOrCreate('Admin');

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole($adminRole);

        $inactiveTeacher = Teacher::query()->create([
            'name' => 'Inactive Teacher',
            'phone' => '+94 77-123-4567',
            'active' => false,
        ]);

        Teacher::query()->create([
            'name' => 'Active Teacher',
            'phone' => '+94 77-123-9999',
            'active' => true,
        ]);

        $response = $this->actingAs($user)->getJson(route('teacher-lookup', ['q' => '0771234567']));

        $response->assertOk();
        $response->assertJsonMissing([
            'id' => $inactiveTeacher->id,
            'name' => 'Inactive Teacher',
        ]);
    }
}
