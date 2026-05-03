<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Developer - Teachers
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
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Complete Teachers</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($teachersTotal) }}</p>
                    <p class="mt-1 text-xs text-gray-600">All teacher records</p>
                </div>
                <div class="rounded-lg border border-emerald-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Active</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($teachersActive) }}</p>
                    <p class="mt-1 text-xs text-gray-600">Currently active teachers</p>
                </div>
                <div class="rounded-lg border border-rose-100 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">Inactive</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($teachersInactive) }}</p>
                    <p class="mt-1 text-xs text-gray-600">Inactive teachers</p>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-gray-900">Teachers List</h3>
                    <p class="mt-1 text-sm text-gray-600">Dedicated developer view for all teachers.</p>

                    @if($teachers->isEmpty())
                        <div class="mt-4 rounded-md bg-gray-50 p-4 text-sm text-gray-700">No teachers found.</div>
                    @else
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Email</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Phone</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach($teachers as $teacher)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $teacher->name }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $teacher->email ?: '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $teacher->phone ?: '-' }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $teacher->active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">{{ $teacher->active ? 'Active' : 'Inactive' }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">{{ $teachers->links() }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
