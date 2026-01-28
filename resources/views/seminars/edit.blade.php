<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-gray-800">Edit Seminar</h1>
        <p class="text-sm text-gray-500 mt-1">Update seminar details, participants, and settings.</p>
    </x-slot>

    <form action="{{ route('seminars.update', $seminar) }}" method="POST" class="py-6 max-w-7xl mx-auto"
          x-data="{
              extraClassrooms: {{ \Illuminate\Support\Js::from(old('class_room_ids', $selectedClassRooms)) }},
              extraStudents: {{ \Illuminate\Support\Js::from(old('student_ids', $enrolledStudentIds)) }},
              addClassroom() { this.extraClassrooms.push('') },
              removeClassroom(index) { this.extraClassrooms.splice(index, 1) },
              addStudent() { this.extraStudents.push('') },
              removeStudent(index) { this.extraStudents.splice(index, 1) }
          }">
        @csrf @method('PUT')

        <div class="space-y-6">
            <!-- Basic Information Card -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">Seminar Details</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Seminar Name</label>
                        <input type="text" name="name" value="{{ old('name', $seminar->name) }}" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow" required>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Date</label>
                        <input type="date" name="date" value="{{ old('date', $seminar->date?->format('Y-m-d')) }}" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Start Time</label>
                            <input type="time" name="start_time" value="{{ old('start_time', $seminar->start_time) }}" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">End Time</label>
                            <input type="time" name="end_time" value="{{ old('end_time', $seminar->end_time) }}" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Primary Classroom <span class="text-gray-400 font-normal">(Optional)</span></label>
                        <select name="class_room_id" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow">
                            <option value="">-- No Specific Class --</option>
                            @foreach($classRooms as $cr)
                                <option value="{{ $cr->id }}" @selected(old('class_room_id', $seminar->class_room_id)==$cr->id)>{{ $cr->name }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Students from this class are auto-enrolled.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Teacher <span class="text-gray-400 font-normal">(Optional)</span></label>

                        <div
                            x-data="teacherPicker({
                                lookupUrl: '{{ route('teacher-lookup') }}',
                                initialTeacherId: '{{ old('teacher_id', $seminar->teacher_id) }}',
                                initialVisitingTeacherId: '{{ old('visiting_teacher_id', $seminar->visiting_teacher_id) }}'
                            })"
                            x-init="init()"
                            class="relative"
                        >
                            <input
                                type="text"
                                x-model="query"
                                x-on:input.debounce.250ms="search()"
                                x-on:focus="open = true; if (query.length >= 1) search();"
                                x-on:keydown.escape.prevent="open=false"
                                placeholder="Search teacher by name or phone..."
                                class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow"
                                autocomplete="off"
                            />

                            <input type="hidden" name="teacher_id" :value="teacherId">
                            <input type="hidden" name="visiting_teacher_id" :value="visitingTeacherId">

                            <button
                                type="button"
                                x-show="teacherId || visitingTeacherId"
                                x-on:click="clear()"
                                class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-600"
                                title="Clear"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm2.707-10.707a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div
                                x-cloak
                                x-show="open && (results.length > 0 || loading)"
                                x-on:click.away="open = false"
                                class="absolute z-50 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg overflow-hidden"
                            >
                                <div x-show="loading" class="px-4 py-2 text-sm text-gray-500">Searching…</div>
                                <template x-for="item in results" :key="item.type + ':' + item.id">
                                    <button type="button" class="w-full text-left px-4 py-2 text-sm hover:bg-indigo-50" x-on:click="select(item)">
                                        <span x-text="item.label"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 mt-1">Search and select either a regular teacher or a visiting teacher.</p>
                        @error('teacher_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                        @error('visiting_teacher_id')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <!-- Financials Card -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">Financial Information</h2>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Fee per Student</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rs</span>
                            <input type="number" step="0.01" min="0" name="fee_per_student" value="{{ old('fee_per_student', $seminar->fee_per_student) }}" class="pl-10 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Teacher Payment</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500">Rs</span>
                            <input type="number" step="0.01" min="0" name="teacher_payment" value="{{ old('teacher_payment', $seminar->teacher_payment) }}" class="pl-10 w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Participants (Dynamic) -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">Additional Participants</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Manage extra classes or individual students.</p>
                </div>
                <div class="p-6 space-y-8">
                    <!-- Dynamic Classrooms -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">Additional Classrooms</label>
                        
                        <div class="space-y-3">
                            <template x-for="(cls, index) in extraClassrooms" :key="'cls-'+index">
                                <div class="flex items-center gap-2">
                                    <div class="relative flex-grow max-w-md">
                                        <select :name="'class_room_ids[]'" x-model="extraClassrooms[index]" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow" required>
                                            <option value="">-- Select Class --</option>
                                            @foreach($classRooms as $cr)
                                                <option value="{{ $cr->id }}">{{ $cr->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="button" @click="removeClassroom(index)" class="p-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-full transition-colors" title="Remove">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <button type="button" @click="addClassroom()" class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-indigo-600 transition-colors shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Another Class
                        </button>
                    </div>

                    <div class="border-t border-gray-100"></div>

                    <!-- Dynamic Students -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">Additional Individual Students</label>
                        
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
            </div>

            <!-- Notes Card -->
            <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">Additional Notes</h2>
                </div>
                <div class="p-6">
                    <textarea name="notes" rows="4" class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition-shadow">{{ old('notes', $seminar->notes) }}</textarea>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('seminars.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors">Cancel</a>
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 text-white font-medium hover:bg-indigo-700 shadow-sm transition-colors ring-offset-2 focus:ring-2 focus:ring-indigo-500">Update Seminar</button>
            </div>
        </div>
    </form>
</x-app-layout>
