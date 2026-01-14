<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-gray-800">Add Extra Class</h1>
        <p class="text-sm text-gray-500 mt-1">Schedule an extra class session.</p>
    </x-slot>

    <form action="{{ route('extra-classes.store') }}" method="POST" class="py-6 max-w-7xl mx-auto"
          x-data="{
              extraStudents: {{ \Illuminate\Support\Js::from(old('student_ids', [])) }},
              paymentType: '{{ old('payment_type', 'daily') }}',
              addStudent() { this.extraStudents.push('') },
              removeStudent(index) { this.extraStudents.splice(index, 1) }
          }">
        @csrf

        <div class="space-y-6">
            <!-- Details Card -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">Class Details</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                         <label class="block text-sm font-semibold text-gray-700 mb-1">Name</label>
                         <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g., Evening Math" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Payment Type</label>
                        <select name="payment_type" x-model="paymentType" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow" required>
                            <option value="daily" @selected(old('payment_type')==='daily')>Daily (Per Session)</option>
                            <option value="monthly" @selected(old('payment_type')==='monthly')>Monthly</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Fee</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rs</span>
                            <input type="number" step="0.01" min="0" name="fee" value="{{ old('fee') }}" class="pl-10 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Teacher Payment <span class="text-gray-400 font-normal">(Optional)</span></label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rs</span>
                            <input type="number" step="0.01" min="0" name="teacher_payment" value="{{ old('teacher_payment') }}" class="pl-10 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">If this class pays a visiting teacher, set it here.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Date</label>
                        <input type="date" name="date" value="{{ old('date') }}" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                             <label class="block text-sm font-semibold text-gray-700 mb-1">Start Time</label>
                             <input type="time" name="start_time" value="{{ old('start_time') }}" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow">
                        </div>
                        <div>
                             <label class="block text-sm font-semibold text-gray-700 mb-1">End Time</label>
                             <input type="time" name="end_time" value="{{ old('end_time') }}" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Classroom <span class="text-gray-400 font-normal">(Optional)</span></label>
                        <select name="class_room_id" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow">
                            <option value="">-- No Specific Class --</option>
                            @foreach($classRooms as $cr)
                                <option value="{{ $cr->id }}" @selected(old('class_room_id')==$cr->id)>{{ $cr->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Students from this class will be enrolled.</p>
                    </div>

                    <div>
                         <label class="block text-sm font-semibold text-gray-700 mb-1">Visiting Teacher <span class="text-gray-400 font-normal">(Optional)</span></label>
                        <select name="visiting_teacher_id" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow">
                            <option value="">-- Select Teacher --</option>
                            @foreach($visitingTeachers as $vt)
                                <option value="{{ $vt->id }}" @selected(old('visiting_teacher_id')==$vt->id)>{{ $vt->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2" x-show="paymentType === 'monthly'">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Payment Start Date (Monthly)</label>
                        <input type="date" name="payment_start_date" value="{{ old('payment_start_date') }}" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow">
                        <p class="text-xs text-gray-500 mt-1">Month from which payments are tracked for this class.</p>
                    </div>

                    <div class="md:col-span-2">
                        <div class="flex items-center gap-3 mt-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="active" value="1" class="sr-only peer" @checked(old('active', true))>
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                <span class="ml-3 text-sm font-medium text-gray-700">Active Status</span>
                            </label>
                            <p class="text-xs text-gray-500">Inactive classes won't show up in daily schedules.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Participants (Dynamic) -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">Additional Students</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Add individual students to this extra class.</p>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <template x-for="(st, index) in extraStudents" :key="'st-'+index">
                            <div class="flex items-center gap-2">
                                <div class="relative flex-grow max-w-md">
                                    <select :name="'student_ids[]'" x-model="extraStudents[index]" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow" required>
                                        <option value="">-- Select Student --</option>
                                        @foreach($students as $st)
                                            <option value="{{ $st->id }}">{{ $st->name }} ({{ $st->admission_number }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" @click="removeStudent(index)" class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-full transition-colors" title="Remove">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="addStudent()" class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-indigo-600 transition-colors shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Student
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('extra-classes.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors">Cancel</a>
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 shadow-sm transition-colors ring-offset-2 focus:ring-2 focus:ring-indigo-500">Create Extra Class</button>
            </div>
        </div>
    </form>
</x-app-layout>
