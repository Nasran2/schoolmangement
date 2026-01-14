<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-gray-800">Add Visiting Teacher</h1>
        <p class="text-sm text-gray-500 mt-1">Register an external teacher for seminars or extra classes.</p>
    </x-slot>

    <form action="{{ route('visiting-teachers.store') }}" method="POST" class="py-6 max-w-5xl mx-auto">
        @csrf
        
        <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-800">Teacher Details</h2>
            </div>
            
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Full Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g., Dr. Jane Smith" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow" required>
                </div>

                <!-- Contact Info -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Phone Number</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="e.g., 077 123 4567" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="e.g., jane@example.com" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow">
                </div>

                <!-- Professional Info -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Specialty / Subject</label>
                    <input type="text" name="specialty" value="{{ old('specialty') }}" placeholder="e.g., Advanced Mathematics, Music, Elocution" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow">
                </div>

                <!-- Status Toggle -->
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="active" value="1" class="sr-only peer" @checked(old('active', true))>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700">Active Status</span>
                        </label>
                        <p class="text-xs text-gray-500">Inactive teachers won't appear in selection lists.</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end gap-3">
                <a href="{{ route('visiting-teachers.index') }}" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-white hover:shadow-sm transition-all">Cancel</a>
                <button type="submit" class="px-6 py-2 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 shadow-sm transition-all ring-offset-2 focus:ring-2 focus:ring-indigo-500">Save Teacher</button>
            </div>
        </div>
    </form>
</x-app-layout>
