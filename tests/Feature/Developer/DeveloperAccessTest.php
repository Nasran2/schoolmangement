<?php

namespace Tests\Feature\Developer;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeveloperAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_developer_dashboard(): void
    {
        $response = $this->get('/developer/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_non_developer_user_cannot_access_developer_dashboard(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/developer/dashboard');

        $response->assertForbidden();
    }

    public function test_developer_user_can_access_developer_dashboard(): void
    {
        $developerRole = Role::findOrCreate('Developer');

        /** @var User $user */
        $user = User::factory()->create();
        $user->assignRole($developerRole);

        $response = $this->actingAs($user)->get('/developer/dashboard');

        $response->assertOk();
    }
}
