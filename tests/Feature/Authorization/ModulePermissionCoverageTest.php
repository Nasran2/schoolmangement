<?php

namespace Tests\Feature\Authorization;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ModulePermissionCoverageTest extends TestCase
{
    use RefreshDatabase;

    public static function modules(): array
    {
        return [
            ['students.manage', 'students.index'], ['students.add', 'students.create'],
            ['teachers.manage', 'teachers.index'], ['teachers.add', 'teachers.create'],
            ['classrooms.view', 'classrooms.index'], ['classrooms.create', 'classrooms.create'],
            ['revenue.manage', 'revenue.items.index'], ['revenue.add', 'revenue.items.create'],
            ['expense.manage', 'expense.items.index'], ['expense.add', 'expense.items.create'],
            ['revenue.categories.manage', 'revenue.categories.index'], ['expense.categories.manage', 'expense.categories.index'],
            ['teachers.salary.pay', 'teacher-salary-payments.index'], ['teachers.salary.summary.view', 'teacher-salary-payments.summary'],
            ['seminars.manage', 'seminars.index'], ['seminars.manage', 'seminars.create'], ['seminars.manage', 'seminars.reports.due'],
            ['extra_classes.manage', 'extra-classes.index'], ['extra_classes.manage', 'extra-classes.create'],
            ['visiting_teachers.manage', 'visiting-teachers.index'], ['visiting_teachers.manage', 'visiting-teachers.create'],
            ['users.manage', 'users.index'], ['users.manage', 'users.create'], ['roles.manage', 'rbac.roles.index'],
            ['audit_logs.view', 'audit_logs.index'], ['settings.general.manage', 'settings.general.edit'],
            ['settings.printer.manage', 'settings.printer.edit'], ['settings.promotion.manage', 'settings.promotion.edit'],
            ['settings.sms.manage', 'settings.sms.edit'], ['settings.email.manage', 'settings.email.edit'],
            ['settings.salary_components.manage', 'settings.salary-components.edit'],
            ['settings.opening_balance.manage', 'settings.opening-balance.edit'],
            ['settings.backups.manage', 'settings.backups.index'],
        ];
    }

    #[DataProvider('modules')]
    public function test_custom_roles_control_module_access(string $permission, string $route): void
    {
        $user = User::factory()->create();
        $role = Role::findOrCreate('Custom Staff', 'web');
        $user->assignRole($role);
        $this->actingAs($user)->get(route($route))->assertForbidden();
        $role->givePermissionTo($permission);
        $this->actingAs($user->fresh())->get(route($route))->assertOk();
    }

    public function test_every_permission_protected_route_rejects_unprivileged_users(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withoutMiddleware(SubstituteBindings::class);
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            $guarded = collect($route->gatherMiddleware())->contains(fn ($middleware) => is_string($middleware) && (str_starts_with($middleware, 'permission:') || str_starts_with($middleware, 'role_or_permission:')));
            if (! $guarded || ! $route->getName()) {
                continue;
            }
            $parameters = array_fill_keys($route->parameterNames(), 999999);
            $url = route($route->getName(), $parameters);
            $this->call($route->methods()[0], $url)->assertForbidden();
            $checked++;
        }
        $this->assertGreaterThan(150, $checked);
    }

    public function test_every_report_card_opens_with_its_permission_and_requires_it(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('reports.view');
        $this->actingAs($user);
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->getName() ?? '', 'reports.') || $route->parameterNames()) {
                continue;
            }
            $permission = collect($route->gatherMiddleware())->first(fn ($middleware) => str_starts_with($middleware, 'permission:reports.') && $middleware !== 'permission:reports.view');
            if (! $permission || str_contains($permission, 'reports.download')) {
                continue;
            }
            $name = substr($permission, strlen('permission:'));
            $this->get(route($route->getName()))->assertForbidden();
            $user->givePermissionTo($name);
            $this->get(route($route->getName()))->assertOk();
            $user->revokePermissionTo($name);
            $checked++;
        }
        $this->assertGreaterThanOrEqual(20, $checked);
    }
}
