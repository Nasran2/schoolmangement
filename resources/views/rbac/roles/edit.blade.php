<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Role Permissions') }} — {{ $role->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('rbac.roles.update', $role) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($permissions as $permission)
                                <label class="inline-flex items-center gap-2 rounded border px-3 py-2">
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $permission->name }}"
                                        class="rounded border-gray-300"
                                        {{ in_array($permission->name, old('permissions', $rolePermissions), true) ? 'checked' : '' }}
                                    />
                                    <span class="text-sm text-gray-800">{{ $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>{{ __('Save Permissions') }}</x-primary-button>
                            <a href="{{ route('rbac.roles.index') }}" class="text-sm text-gray-600 hover:underline">Back</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
