<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Manage Role Permissions') }} — <span class="text-indigo-600">{{ $role->name }}</span>
            </h2>
            <a href="{{ route('rbac.roles.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                {{ __('Back to Roles') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm text-green-800 shadow-sm border border-green-100">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('rbac.roles.update', $role) }}">
                @csrf
                @method('PUT')

                <div class="space-y-8">
                    @if (!empty($permissionGroups ?? []))
                        @foreach ($permissionGroups as $group => $perms)
                            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                                    <h3 class="text-lg font-medium text-gray-900">{{ $group }}</h3>
                                    <span class="text-xs font-medium text-gray-500 bg-white px-2 py-1 rounded border border-gray-200">{{ count($perms) }} permissions</span>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach ($perms as $permission)
                                            <label class="relative flex items-start py-2 cursor-pointer group hover:bg-gray-50 rounded-md px-2 -mx-2 transition-colors duration-150">
                                                <div class="flex items-center h-5 mt-0.5">
                                                    <input
                                                        type="checkbox"
                                                        name="permissions[]"
                                                        value="{{ $permission->name }}"
                                                        class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 transition duration-150 ease-in-out"
                                                        {{ in_array($permission->name, old('permissions', $rolePermissions), true) ? 'checked' : '' }}
                                                    />
                                                </div>
                                                <div class="ml-3 text-sm leading-5 select-none">
                                                    <span class="font-medium text-gray-700 group-hover:text-gray-900 transition duration-150 ease-in-out block">
                                                        {{ $permissionLabels[$permission->name] ?? ucwords(str_replace('.', ' ', $permission->name)) }}
                                                    </span>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        {{-- Fallback if no groups --}}
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($permissions as $permission)
                                        <label class="flex items-center space-x-3">
                                            <input
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission->name }}"
                                                class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                                                {{ in_array($permission->name, old('permissions', $rolePermissions), true) ? 'checked' : '' }}
                                            />
                                            <span class="text-sm text-gray-700">{{ $permissionLabels[$permission->name] ?? $permission->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-8 flex items-center justify-end gap-4 bg-white p-4 rounded-lg shadow-sm border border-gray-200 sticky bottom-4 z-10">
                    <a href="{{ route('rbac.roles.index') }}" class="text-sm text-gray-600 hover:text-gray-900 font-medium">Cancel</a>
                    <x-primary-button class="px-6 py-2 text-base shadow-md">{{ __('Save Changes') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
