<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Expense Categories</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('expense.categories.store') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                        @csrf
                        <div class="sm:col-span-3">
                            <x-input-label for="name" :value="__('Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>
                        <div class="flex items-end">
                            <x-primary-button>Create</x-primary-button>
                        </div>

                        <div class="sm:col-span-4">
                            <x-input-label for="description" :value="__('Description (optional)')" />
                            <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" />
                            <x-input-error class="mt-2" :messages="$errors->get('description')" />
                        </div>
                    </form>

                    <div class="mt-8 divide-y rounded border">
                        <div class="grid grid-cols-12 px-4 py-2 text-xs font-semibold text-gray-600">
                            <div class="col-span-8">Name</div>
                            <div class="col-span-2">Status</div>
                            <div class="col-span-2 text-right">Actions</div>
                        </div>
                        @forelse ($categories as $cat)
                            <div class="grid grid-cols-12 items-center px-4 py-3">
                                <div class="col-span-8 text-sm font-medium text-gray-900">{{ $cat->name }}</div>
                                <div class="col-span-2 text-sm text-gray-700">{{ $cat->active ? 'Active' : 'Disabled' }}</div>
                                <div class="col-span-2 flex justify-end gap-2">
                                    <a href="{{ route('expense.categories.edit', $cat) }}" class="text-sm text-indigo-600 hover:underline">Edit</a>
                                    <span x-data="{ open:false }">
                                        <button type="button" class="text-sm text-red-600 hover:underline" x-on:click="open=true">Delete</button>
                                        <form x-ref="delForm" class="hidden" method="POST" action="{{ route('expense.categories.destroy', $cat) }}">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-center justify-center">
                                            <div class="absolute inset-0 bg-black/40" x-on:click="open=false"></div>
                                            <div class="relative z-10 w-full max-w-sm rounded-md bg-white p-5 shadow-lg">
                                                <div class="text-sm font-semibold text-gray-800">Delete Category</div>
                                                <div class="mt-2 text-sm text-gray-600">Are you sure you want to delete this category?</div>
                                                <div class="mt-4 flex justify-end gap-2">
                                                    <button type="button" class="rounded-md border px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50" x-on:click="open=false">Cancel</button>
                                                    <button type="button" class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-red-700" x-on:click="$refs.delForm.submit()">Delete</button>
                                                </div>
                                            </div>
                                        </div>
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="px-4 py-3 text-sm text-gray-600">No expense categories yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
