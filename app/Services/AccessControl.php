<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AccessControl
{
    public const SYSTEM_ROLES = ['Super Admin', 'Admin', 'Developer'];

    public function assignableRoles(User $actor): Collection
    {
        return Role::query()->with('permissions')->where('guard_name', 'web')->orderBy('name')->get()
            ->filter(fn (Role $role) => $this->canAssignRole($actor, $role))->values();
    }

    public function canAssignRole(User $actor, Role $role): bool
    {
        if (strcasecmp($role->name, 'Developer') === 0) {
            return false;
        }

        if ($actor->hasRole('Super Admin')) {
            return true;
        }

        if (in_array($role->name, self::SYSTEM_ROLES, true)) {
            return false;
        }

        return $role->permissions->every(fn ($permission) => $actor->can($permission->name));
    }

    public function authorizeTarget(User $actor, User $target): void
    {
        abort_if($target->hasRole('Developer'), 403, 'Developer accounts are managed in the developer console.');
        abort_unless($this->canManageTarget($actor, $target), 403);
    }

    public function canManageTarget(User $actor, User $target): bool
    {
        if ($target->hasRole('Developer')) {
            return false;
        }
        if ($actor->is($target)) {
            return true;
        }

        if ($target->hasAnyRole(['Super Admin', 'Admin']) && ! $actor->hasRole('Super Admin')) {
            return false;
        }

        return $target->getAllPermissions()->every(fn ($permission) => $actor->can($permission->name));
    }

    public function validateRoles(User $actor, array $names): void
    {
        $roles = Role::query()->with('permissions')->where('guard_name', 'web')->whereIn('name', $names)->get();
        if ($roles->count() !== count(array_unique($names)) || $roles->contains(fn (Role $role) => ! $this->canAssignRole($actor, $role))) {
            throw ValidationException::withMessages(['roles' => 'Choose roles you are authorized to assign.']);
        }
    }

    public function protectLastAdministrator(User $target, bool $active, array $roles): void
    {
        if (! $target->active || ! $target->hasAnyRole(['Super Admin', 'Admin'])) {
            return;
        }

        $requiredRoles = $target->hasRole('Super Admin') ? ['Super Admin'] : ['Super Admin', 'Admin'];
        if ($active && array_intersect($requiredRoles, $roles)) {
            return;
        }

        if (! User::role($requiredRoles)->where('active', true)->whereKeyNot($target->id)->exists()) {
            throw ValidationException::withMessages(['status' => 'At least one active '.($target->hasRole('Super Admin') ? 'Super Admin' : 'administrator').' account is required.']);
        }
    }
}
