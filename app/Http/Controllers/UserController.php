<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccessControl;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private AccessControl $access) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'in:0,1'],
        ]);
        $base = User::query()->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', ['Developer', 'developer']));
        $hasActiveColumn = Schema::hasColumn('users', 'active');
        $stats = [
            'total' => (clone $base)->count(),
            'active' => $hasActiveColumn ? (clone $base)->where('active', true)->count() : (clone $base)->count(),
        ];
        $users = $base->with('roles')
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('username', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'));
            })
            ->when($filters['role'] ?? null, fn ($query, $role) => $query->whereHas('roles', fn ($q) => $q->where('name', $role)))
            ->when($hasActiveColumn && isset($filters['active']), fn ($query) => $query->where('active', $filters['active']))
            ->orderBy('name')->paginate(25)->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => Role::query()->where('guard_name', 'web')->whereNotIn('name', ['Developer', 'developer'])->orderBy('name')->get(),
            'hasActiveColumn' => $hasActiveColumn,
            'stats' => $stats,
        ]);
    }

    public function create(Request $request): View
    {
        return view('users.create', [
            'roles' => $this->access->assignableRoles($request->user()),
            'hasActiveColumn' => Schema::hasColumn('users', 'active'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateUser($request);
        $roles = $validated['roles'];
        $this->access->validateRoles($request->user(), $roles);

        DB::transaction(function () use ($validated, $roles) {
            $user = User::create(collect($validated)->except('roles')->all());
            $user->syncRoles($roles);
            app(AuditLogger::class)->log('users.create', $user, 'User account created', [
                'roles' => $roles, 'active' => $user->active,
            ]);
        });

        return redirect()->route('users.index')->with('status', 'User created successfully.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->access->authorizeTarget($request->user(), $user);

        return view('users.edit', [
            'user' => $user->load('roles'),
            'roles' => $this->access->assignableRoles($request->user())->merge($request->user()->is($user) ? $user->roles : []),
            'hasActiveColumn' => Schema::hasColumn('users', 'active'),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->access->authorizeTarget($request->user(), $user);
        $validated = $this->validateUser($request, $user);
        $roles = $validated['roles'];
        if ($request->user()->is($user)) {
            if (! $validated['active'] || collect($roles)->sort()->values()->all() !== $user->getRoleNames()->sort()->values()->all()) {
                throw ValidationException::withMessages(['roles' => 'You cannot change your own roles or deactivate your own account.']);
            }
        } else {
            $this->access->validateRoles($request->user(), $roles);
        }

        DB::transaction(function () use ($user, $validated, $roles) {
            User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))->lockForUpdate()->get();
            $this->access->protectLastAdministrator($user, $validated['active'], $roles);
            $data = collect($validated)->except('roles')->all();
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['remember_token'] = Str::random(60);
            }
            if ($user->email !== $data['email']) {
                $data['email_verified_at'] = null;
            }
            $user->forceFill($data)->save();
            $user->syncRoles($roles);
            app(AuditLogger::class)->log('users.update', $user, 'User account updated', [
                'roles' => $roles, 'active' => $user->active, 'password_changed' => isset($data['password']),
            ]);
        });

        return redirect()->route('users.index')->with('status', 'User updated successfully.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $this->access->authorizeTarget($request->user(), $user);
        $validated = $request->validate(['active' => ['required', 'boolean']]);
        $active = (bool) $validated['active'];
        if (! $active && $request->user()->is($user)) {
            throw ValidationException::withMessages(['status' => 'You cannot deactivate your own account.']);
        }
        DB::transaction(function () use ($user, $active) {
            User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))->lockForUpdate()->get();
            $this->access->protectLastAdministrator($user, $active, $user->getRoleNames()->all());
            $before = $user->active;
            $user->forceFill(['active' => $active, 'remember_token' => Str::random(60)])->save();
            app(AuditLogger::class)->log('users.status.update', $user, 'User active status updated', ['before' => $before, 'after' => $active]);
        });

        return back()->with('status', $active ? 'User activated.' : 'User deactivated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->access->authorizeTarget($request->user(), $user);
        if ($request->user()->is($user)) {
            throw ValidationException::withMessages(['status' => 'You cannot delete your own account from user management.']);
        }
        DB::transaction(function () use ($user) {
            User::query()->whereHas('roles', fn ($q) => $q->where('name', 'Super Admin'))->lockForUpdate()->get();
            $this->access->protectLastAdministrator($user, false, []);
            app(AuditLogger::class)->log('users.delete', $user, 'User account deleted', ['name' => $user->name, 'roles' => $user->getRoleNames()->all()]);
            $user->delete();
        });

        return redirect()->route('users.index')->with('status', 'User deleted successfully.');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        // Accept the original single-role form as well as the multiple-role editor.
        if (! $request->has('roles')) {
            $request->merge(['roles' => $request->filled('role') ? [$request->input('role')] : []]);
        }
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'confirmed', Password::defaults()],
            'roles' => ['present', 'array'],
            'roles.*' => ['required', 'string', 'distinct', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'active' => ['sometimes', 'required', 'boolean'],
        ]);
        $validated['active'] = (bool) ($validated['active'] ?? $user?->active ?? true);

        return $validated;
    }
}
