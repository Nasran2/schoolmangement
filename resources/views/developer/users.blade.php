<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Developer - Users
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-lg border border-indigo-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Complete Users</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($usersTotal) }}</p>
                    <p class="mt-1 text-xs text-gray-600">All user records</p>
                </div>
                <div class="rounded-lg border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Active</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($usersActive) }}</p>
                    <p class="mt-1 text-xs text-gray-600">Users that can login</p>
                </div>
                <div class="rounded-lg border border-rose-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">Inactive</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($usersInactive) }}</p>
                    <p class="mt-1 text-xs text-gray-600">Deactivated users</p>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-base font-semibold text-gray-900">User Access Control</h3>
                        <p class="text-xs text-gray-500">Activate or deactivate users from this page.</p>
                    </div>

                    @if(!$usersHaveActiveColumn)
                        <div class="mt-4 rounded-md bg-yellow-50 p-3 text-sm text-yellow-800">
                            User status control is unavailable until the users.active column exists.
                        </div>
                    @else
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Role</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach($users as $u)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                <div class="font-semibold">{{ $u->name }}</div>
                                                <div class="text-xs text-gray-500">{{ $u->username }} | {{ $u->email }}</div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $u->roles->pluck('name')->join(', ') ?: 'No Role' }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $u->active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ $u->active ? 'Active' : 'Inactive' }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-right text-sm">
                                                @if(auth()->id() === $u->id)
                                                    <span class="inline-flex items-center rounded-md bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-600">Current User</span>
                                                @else
                                                    <form method="POST" action="{{ route('developer.users.status', $u) }}" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="active" value="{{ $u->active ? 0 : 1 }}">
                                                        <button type="submit" class="inline-flex items-center rounded-md px-3 py-2 text-xs font-semibold text-white {{ $u->active ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}" onclick="return confirm('{{ $u->active ? 'Deactivate' : 'Activate' }} this user?');">
                                                            {{ $u->active ? 'Deactivate' : 'Activate' }}
                                                        </button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">{{ $users->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
