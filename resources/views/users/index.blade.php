<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900">Users</h2>
                <p class="text-gray-600 text-sm mt-1">Create accounts and manage access for staff members</p>
            </div>
            <a href="{{ route('users.create') }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700">
                + New User
            </a>
        </div>
    </x-slot>

    <div class="py-10 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-xl border border-indigo-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Users</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($stats['total']) }}</p>
                </div>
                <div class="rounded-xl border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Active</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $hasActiveColumn ? number_format($stats['active']) : number_format($stats['total']) }}</p>
                </div>
                <div class="rounded-xl border border-amber-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Roles</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($roles->count()) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900">User List</h3>
                    <form method="GET" class="mt-4 flex flex-wrap items-end gap-3">
                        <div><x-input-label for="q" value="Search users" /><x-text-input id="q" name="q" :value="request('q')" placeholder="Name, username or email" /></div>
                        <div><x-input-label for="filter-role" value="Role" /><select id="filter-role" name="role" class="rounded-md border-gray-300"><option value="">All roles</option>@foreach($roles as $role)<option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ $role->name }}</option>@endforeach</select></div>
                        <div><x-input-label for="filter-active" value="Status" /><select id="filter-active" name="active" class="rounded-md border-gray-300"><option value="">All statuses</option><option value="1" @selected(request('active') === '1')>Active</option><option value="0" @selected(request('active') === '0')>Inactive</option></select></div>
                        <x-primary-button>Filter</x-primary-button><a href="{{ route('users.index') }}" class="py-2 text-sm text-indigo-600">Reset</a>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">User</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($users as $user)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-gray-900">{{ $user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $user->username }} | {{ $user->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $user->roles->pluck('name')->join(', ') ?: 'No Role' }}</td>
                                    <td class="px-6 py-4">
                                        @if($hasActiveColumn)
                                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $user->active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                                {{ $user->active ? 'Active' : 'Inactive' }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-500">Status unavailable</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        @if(app(\App\Services\AccessControl::class)->canManageTarget(auth()->user(), $user))
                                        <a href="{{ route('users.edit', $user) }}" class="inline-flex px-3 py-2 text-xs font-semibold text-indigo-600">Edit</a>
                                        @if($hasActiveColumn)
                                            @if(auth()->id() === $user->id)
                                                <span class="inline-flex items-center rounded-md bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-600">Current User</span>
                                            @else
                                                <form method="POST" action="{{ route('users.status', $user) }}" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="active" value="{{ $user->active ? 0 : 1 }}">
                                                    <button type="submit" class="inline-flex items-center rounded-md px-3 py-2 text-xs font-semibold text-white {{ $user->active ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}">
                                                        {{ $user->active ? 'Deactivate' : 'Activate' }}
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <span class="text-xs text-gray-500">Status unavailable</span>
                                        @endif
                                        @if(auth()->id() !== $user->id)
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" onsubmit="return confirm('Delete this user account permanently?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="px-3 py-2 text-xs font-semibold text-red-700">Delete</button>
                                            </form>
                                        @endif
                                        @else
                                            <span class="text-xs text-gray-500">Protected account</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
