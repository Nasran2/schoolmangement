<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with('roles')
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', ['Developer', 'developer']);
            })
            ->orderBy('name')
            ->paginate(25);

        $roles = Role::query()->orderBy('name')->get();
        $hasActiveColumn = Schema::hasColumn('users', 'active');

        return view('users.index', [
            'users' => $users,
            'roles' => $roles,
            'hasActiveColumn' => $hasActiveColumn,
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'roles' => Role::query()->orderBy('name')->get(),
            'hasActiveColumn' => Schema::hasColumn('users', 'active'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed'],
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')],
            'active' => ['nullable', 'in:0,1'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'active' => Schema::hasColumn('users', 'active') ? (($validated['active'] ?? '1') === '1') : true,
        ]);

        if (! empty($validated['role'])) {
            $user->assignRole($validated['role']);
        }

        app(AuditLogger::class)->log(
            'users.create',
            $user,
            'User account created',
            [
                'user_id' => $user->id,
                'role' => $validated['role'] ?? null,
                'active' => (bool) $user->active,
            ]
        );

        return redirect()->route('users.index')->with('status', 'User created successfully.');
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        if (! Schema::hasColumn('users', 'active')) {
            return back()->withErrors(['status' => 'User status column is missing. Please run migrations first.']);
        }

        $validated = $request->validate([
            'active' => ['required', 'in:0,1'],
        ]);

        $targetActive = $validated['active'] === '1';
        $actor = $request->user();

        if (! $targetActive && $actor && $actor->id === $user->id) {
            return back()->withErrors(['status' => 'You cannot deactivate your own account.']);
        }

        if (! $targetActive && $user->hasRole('Developer')) {
            $activeDeveloperCount = User::role('Developer')
                ->where('active', true)
                ->count();

            if ($activeDeveloperCount <= 1) {
                return back()->withErrors(['status' => 'At least one active Developer account is required.']);
            }
        }

        $before = (bool) $user->active;
        $user->forceFill(['active' => $targetActive])->save();

        app(AuditLogger::class)->log(
            'users.status.update',
            $user,
            'User active status updated',
            [
                'before' => $before,
                'after' => $targetActive,
                'updated_by' => $actor?->id,
            ]
        );

        return back()->with('status', $targetActive ? 'User activated.' : 'User deactivated.');
    }
}
