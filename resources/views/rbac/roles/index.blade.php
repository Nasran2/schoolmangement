<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Roles') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('rbac.roles.store') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                        @csrf
                        <div class="flex-1">
                            <x-input-label for="name" :value="__('New Role Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>
                        <x-primary-button>{{ __('Create') }}</x-primary-button>
                    </form>

                    <div class="mt-8">
                        <h3 class="text-base font-semibold text-gray-900">Existing Roles</h3>
                        <div class="mt-3 divide-y rounded border">
                            @forelse ($roles as $role)
                                <div class="flex items-center justify-between px-4 py-3">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $role->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $role->users_count }} users · {{ in_array($role->name, ['Admin', 'Super Admin'], true) ? 'Full application access' : ($role->name === 'Developer' ? 'Developer console access' : $role->permissions_count.' permissions') }}{{ in_array($role->name, \App\Services\AccessControl::SYSTEM_ROLES, true) ? ' · Protected system role' : '' }}</div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <a href="{{ route('rbac.roles.edit', $role) }}" class="text-sm text-indigo-600 hover:underline">{{ in_array($role->name, \App\Services\AccessControl::SYSTEM_ROLES, true) ? 'View Access' : 'Manage Permissions' }}</a>
                                        @unless(in_array($role->name, \App\Services\AccessControl::SYSTEM_ROLES, true))
                                            <form method="POST" action="{{ route('rbac.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role? Users must be reassigned first.');">@csrf @method('DELETE')<button type="submit" class="text-sm text-red-700">Delete</button></form>
                                        @endunless
                                    </div>
                                </div>
                            @empty
                                <div class="px-4 py-3 text-sm text-gray-600">No roles yet.</div>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
