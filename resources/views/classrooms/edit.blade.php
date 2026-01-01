<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Class Room') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <form method="POST" action="{{ route('classrooms.update', $item) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="level" value="Level" />
                        <x-text-input id="level" name="level" type="number" class="mt-1 block w-full" :value="old('level', $item->level)" />
                        <x-input-error :messages="$errors->get('level')" class="mt-2" />
                        <p class="mt-1 text-xs text-gray-500">Use 0,1,2... (used for automatic class promotion each academic year).</p>
                    </div>

                    <div>
                        <x-input-label for="name" value="Name" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $item->name)" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="monthly_fee" value="Monthly Fee" />
                            <x-text-input id="monthly_fee" name="monthly_fee" type="number" step="0.01" class="mt-1 block w-full" :value="old('monthly_fee', $item->monthly_fee ?? 0)" />
                            <x-input-error :messages="$errors->get('monthly_fee')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="monthly_fee_revenue_category_id" value="Monthly Fee Category" />
                            <select id="monthly_fee_revenue_category_id" name="monthly_fee_revenue_category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">None</option>
                                @foreach ($monthlyCategories as $cat)
                                    <option value="{{ $cat->id }}" @selected((string) old('monthly_fee_revenue_category_id', $item->monthly_fee_revenue_category_id) === (string) $cat->id)>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('monthly_fee_revenue_category_id')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="description" value="Description" />
                        <x-text-input id="description" name="description" class="mt-1 block w-full" :value="old('description', $item->description)" />
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="active" name="active" type="checkbox" value="1" class="rounded border-gray-300" @checked(old('active', $item->active))>
                        <label for="active" class="text-sm text-gray-700">Active</label>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-primary-button>Update</x-primary-button>
                        <a href="{{ route('classrooms.index') }}" class="text-sm text-gray-700 underline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
