<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Expenses</h2>
            <div class="flex gap-4">
                @can('expense.categories.manage')
                    <a href="{{ route('expense.categories.index') }}" class="text-sm text-gray-600 hover:underline">Categories</a>
                @endcan
                @can('expense.add')
                    <a href="{{ route('expense.items.create') }}" class="text-sm text-indigo-600 hover:underline">Add Expense</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                        <div>
                            <x-input-label for="category_id" :value="__('Category')" />
                            <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">All</option>
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ ($filters['category_id'] ?? '') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="from" :value="__('From')" />
                            <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="($filters['from'] ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="to" :value="__('To')" />
                            <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="($filters['to'] ?? '')" />
                        </div>
                        <div class="flex items-end gap-3">
                            <x-primary-button>Filter</x-primary-button>
                            <a href="{{ route('expense.items.index') }}" class="text-sm text-gray-600 hover:underline">Reset</a>
                        </div>
                    </form>

                    <div class="mt-6 overflow-x-auto rounded border">
                        <table class="min-w-full divide-y">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Category</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Amount</th>
                                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                @forelse ($items as $item)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-800">{{ optional($item->expense_date)->format('Y-m-d') }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-800">{{ $item->category?->name }}</td>
                                        <td class="px-4 py-2 text-right text-sm font-semibold text-gray-900">{{ number_format($item->amount, 2) }}</td>
                                        <td class="px-4 py-2 text-right text-sm">
                                            <a href="{{ route('expense.items.edit', $item) }}" class="text-indigo-600 hover:underline">Edit</a>
                                            <span x-data="{ open:false }" class="inline-block">
                                                <button type="button" class="ms-2 text-red-600 hover:underline" x-on:click="open=true">Delete</button>
                                                <form x-ref="delForm" class="hidden" method="POST" action="{{ route('expense.items.destroy', $item) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                                <div x-cloak x-show="open" class="fixed inset-0 z-50 flex items-center justify-center">
                                                    <div class="absolute inset-0 bg-black/40" x-on:click="open=false"></div>
                                                    <div class="relative z-10 w-full max-w-sm rounded-md bg-white p-5 shadow-lg">
                                                        <div class="text-sm font-semibold text-gray-800">Delete Expense</div>
                                                        <div class="mt-2 text-sm text-gray-600">Are you sure you want to delete this expense?</div>
                                                        <div class="mt-4 flex justify-end gap-2">
                                                            <button type="button" class="rounded-md border px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50" x-on:click="open=false">Cancel</button>
                                                            <button type="button" class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-red-700" x-on:click="$refs.delForm.submit()">Delete</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td class="px-4 py-4 text-sm text-gray-600" colspan="4">No expense records found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">{{ $items->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
