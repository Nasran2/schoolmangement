<?php

namespace App\Http\Controllers\Rbac;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('rbac.roles.index', [
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function edit(Role $role): View
    {
        $permissions = Permission::query()->orderBy('name')->get();

        return view('rbac.roles.edit', [
            'role' => $role,
            'permissions' => $permissions,
            'rolePermissions' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return back()->with('status', 'Role permissions updated.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
        ]);

        Role::create(['name' => $validated['name']]);

        return back()->with('status', 'Role created.');
    }
}
