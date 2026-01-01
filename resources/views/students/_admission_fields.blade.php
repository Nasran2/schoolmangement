@php
    $S = isset($student) ? $student : null;
    $req = [
        'first_name', 'name_with_initial', 'gender', 'date_of_birth',
        'parent_address', 'religion', 'desired_class',
        'long_term_medication', 'learning_disabilities', 'has_siblings_in_college'
    ];
    $star = function($field) use ($req) { return in_array($field, $req, true) ? '<span class="text-red-600">*</span>' : ''; };
@endphp

<div x-data="{ useGuardian: {{ old('use_guardian', $S->use_guardian ?? 0) ? 'true' : 'false' }} }">

    <!-- Student Details Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center mb-6 pb-2 border-b border-gray-100">
            <div class="bg-blue-100 p-2 rounded-lg mr-3">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800">Student Details</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Name Fields -->
            <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="first_name">
                        {{ __('First Name') }} {!! $star('first_name') !!}
                    </x-input-label>
                    <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full"
                        :value="old('first_name', $S->first_name ?? '')" required />
                    <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
                </div>
                <div>
                    <x-input-label for="other_names" :value="__('Other Names')" />
                    <x-text-input id="other_names" name="other_names" type="text" class="mt-1 block w-full"
                        :value="old('other_names', $S->other_names ?? '')" />
                    <x-input-error class="mt-2" :messages="$errors->get('other_names')" />
                </div>
                <div class="md:col-span-2">
                    <x-input-label for="name_with_initial">
                        {{ __('Name With Initial') }} {!! $star('name_with_initial') !!}
                    </x-input-label>
                    <x-text-input id="name_with_initial" name="name_with_initial" type="text" class="mt-1 block w-full"
                        :value="old('name_with_initial', $S->name_with_initial ?? '')" required />
                    <x-input-error class="mt-2" :messages="$errors->get('name_with_initial')" />
                </div>
            </div>

            <!-- Personal Info -->
            <div>
                <x-input-label for="gender">
                    {{ __('Gender') }} {!! $star('gender') !!}
                </x-input-label>
                <select id="gender" name="gender" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @php $g = old('gender', $S->gender ?? ''); @endphp
                    <option value="" {{ $g==='' ? 'selected' : '' }}>Select Gender</option>
                    <option value="Male" {{ $g==='Male' ? 'selected' : '' }}>Male</option>
                    <option value="Female" {{ $g==='Female' ? 'selected' : '' }}>Female</option>
                    <option value="Other" {{ $g==='Other' ? 'selected' : '' }}>Other</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('gender')" />
            </div>
            <div>
                <x-input-label for="date_of_birth">
                    {{ __('Date of Birth') }} {!! $star('date_of_birth') !!}
                </x-input-label>
                <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full"
                    :value="old('date_of_birth', optional($S->date_of_birth ?? null)->format('Y-m-d'))" required />
                <x-input-error class="mt-2" :messages="$errors->get('date_of_birth')" />
            </div>
            <div>
                <x-input-label for="religion">
                    {{ __('Religion') }} {!! $star('religion') !!}
                </x-input-label>
                <x-text-input id="religion" name="religion" type="text" class="mt-1 block w-full"
                    :value="old('religion', $S->religion ?? '')" required />
                <x-input-error class="mt-2" :messages="$errors->get('religion')" />
            </div>

            <!-- Academic Info -->
            <div>
                <x-input-label for="desired_class">
                    {{ __('Desired Class (at admission)') }} {!! $star('desired_class') !!}
                </x-input-label>
                <x-text-input id="desired_class" name="desired_class" type="text" class="mt-1 block w-full"
                    :value="old('desired_class', $S->desired_class ?? '')" required />
                <x-input-error class="mt-2" :messages="$errors->get('desired_class')" />
            </div>
            <div>
                <x-input-label for="joining_date" :value="__('Joining Date')" />
                <x-text-input id="joining_date" name="joining_date" type="date" class="mt-1 block w-full"
                    :value="old('joining_date', optional($S->joining_date ?? null)->format('Y-m-d'))" />
                <p class="text-xs text-gray-500 mt-1">Academic Year will be calculated automatically</p>
                <x-input-error class="mt-2" :messages="$errors->get('joining_date')" />
            </div>
            <div>
                <x-input-label for="previous_school" :value="__('Previous School')" />
                <x-text-input id="previous_school" name="previous_school" type="text" class="mt-1 block w-full"
                    :value="old('previous_school', $S->previous_school ?? '')" />
            </div>
            <div>
                <x-input-label for="previous_grade" :value="__('Grade Studied')" />
                <x-text-input id="previous_grade" name="previous_grade" type="text" class="mt-1 block w-full"
                    :value="old('previous_grade', $S->previous_grade ?? '')" />
            </div>

            <!-- Medical & Siblings -->
            <div class="md:col-span-2">
                <x-input-label for="medical_history" :value="__('Medical History')" />
                <textarea id="medical_history" name="medical_history" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('medical_history', $S->medical_history ?? '') }}</textarea>
            </div>
            
            <div class="bg-gray-50 p-4 rounded-lg">
                <span class="text-sm font-medium text-gray-700">Long-term Medication {!! $star('long_term_medication') !!}</span>
                <div class="mt-2 flex gap-4">
                    @php $ltm = (string) old('long_term_medication', isset($S) ? ((int)($S->long_term_medication ?? 0)) : ''); @endphp
                    <label class="inline-flex items-center"><input type="radio" name="long_term_medication" value="1" {{ $ltm==='1' ? 'checked' : '' }} class="text-blue-600" required> <span class="ml-2">Yes</span></label>
                    <label class="inline-flex items-center"><input type="radio" name="long_term_medication" value="0" {{ $ltm==='0' ? 'checked' : '' }} class="text-blue-600" required> <span class="ml-2">No</span></label>
                </div>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
                <span class="text-sm font-medium text-gray-700">Learning Disabilities {!! $star('learning_disabilities') !!}</span>
                <div class="mt-2 flex gap-4">
                    @php $ld = (string) old('learning_disabilities', isset($S) ? ((int)($S->learning_disabilities ?? 0)) : ''); @endphp
                    <label class="inline-flex items-center"><input type="radio" name="learning_disabilities" value="1" {{ $ld==='1' ? 'checked' : '' }} class="text-blue-600" required> <span class="ml-2">Yes</span></label>
                    <label class="inline-flex items-center"><input type="radio" name="learning_disabilities" value="0" {{ $ld==='0' ? 'checked' : '' }} class="text-blue-600" required> <span class="ml-2">No</span></label>
                </div>
            </div>

            <div class="md:col-span-2">
                <x-input-label for="siblings" :value="__('Siblings (names)')" />
                <textarea id="siblings" name="siblings" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('siblings', $S->siblings ?? '') }}</textarea>
                <div class="mt-2">
                    <span class="text-sm text-gray-700">Has siblings in college? {!! $star('has_siblings_in_college') !!}</span>
                    <div class="inline-flex ml-4 gap-4">
                        @php $sib = (string) old('has_siblings_in_college', isset($S) ? ((int)($S->has_siblings_in_college ?? 0)) : ''); @endphp
                        <label class="inline-flex items-center"><input type="radio" name="has_siblings_in_college" value="1" {{ $sib==='1' ? 'checked' : '' }} class="text-blue-600" required> <span class="ml-2">Yes</span></label>
                        <label class="inline-flex items-center"><input type="radio" name="has_siblings_in_college" value="0" {{ $sib==='0' ? 'checked' : '' }} class="text-blue-600" required> <span class="ml-2">No</span></label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Parent Details Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-6 pb-2 border-b border-gray-100">
            <div class="flex items-center">
                <div class="bg-purple-100 p-2 rounded-lg mr-3">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Parent Details</h3>
            </div>
            
            <!-- Guardian Toggle -->
            <div class="flex items-center">
                <input type="hidden" name="use_guardian" value="0">
                <input type="checkbox" id="use_guardian" name="use_guardian" value="1" x-model="useGuardian" class="w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                <label for="use_guardian" class="ml-2 text-sm font-medium text-gray-700">
                    Student has a Guardian (No Parents)
                </label>
            </div>
        </div>

        <!-- Parents Form -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Father -->
            <div class="bg-gray-50 p-5 rounded-xl border border-gray-100">
                <h4 class="font-bold text-gray-700 mb-4 flex items-center">
                    <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span> Father's Information
                </h4>
                <div class="space-y-4">
                    <div>
                        <x-input-label for="father_name_with_initial">
                            {{ __('Name with Initial') }} <span x-show="!useGuardian" class="text-red-600">*</span>
                        </x-input-label>
                        <x-text-input id="father_name_with_initial" name="father_name_with_initial" type="text" class="mt-1 block w-full" :value="old('father_name_with_initial', $S->father_name_with_initial ?? '')" />
                    </div>
                    <div>
                        <x-input-label for="father_nic_passport" :value="__('NIC / Passport')" />
                        <x-text-input id="father_nic_passport" name="father_nic_passport" type="text" class="mt-1 block w-full" :value="old('father_nic_passport', $S->father_nic_passport ?? '')" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="father_religion" :value="__('Religion')" />
                            <x-text-input id="father_religion" name="father_religion" type="text" class="mt-1 block w-full" :value="old('father_religion', $S->father_religion ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="father_nationality" :value="__('Nationality')" />
                            <x-text-input id="father_nationality" name="father_nationality" type="text" class="mt-1 block w-full" :value="old('father_nationality', $S->father_nationality ?? '')" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="father_occupation" :value="__('Occupation')" />
                        <x-text-input id="father_occupation" name="father_occupation" type="text" class="mt-1 block w-full" :value="old('father_occupation', $S->father_occupation ?? '')" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="father_phone" :value="__('Mobile')" />
                            <x-text-input id="father_phone" name="father_phone" type="text" class="mt-1 block w-full" :value="old('father_phone', $S->father_phone ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="father_whatsapp" :value="__('WhatsApp')" />
                            <x-text-input id="father_whatsapp" name="father_whatsapp" type="text" class="mt-1 block w-full" :value="old('father_whatsapp', $S->father_whatsapp ?? '')" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="father_office_phone" :value="__('Office Phone')" />
                            <x-text-input id="father_office_phone" name="father_office_phone" type="text" class="mt-1 block w-full" :value="old('father_office_phone', $S->father_office_phone ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="father_emergency_number" :value="__('Emergency')" />
                            <x-text-input id="father_emergency_number" name="father_emergency_number" type="text" class="mt-1 block w-full" :value="old('father_emergency_number', $S->father_emergency_number ?? '')" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mother -->
            <div class="bg-gray-50 p-5 rounded-xl border border-gray-100">
                <h4 class="font-bold text-gray-700 mb-4 flex items-center">
                    <span class="w-2 h-2 bg-pink-500 rounded-full mr-2"></span> Mother's Information
                </h4>
                <div class="space-y-4">
                    <div>
                        <x-input-label for="mother_name_with_initial" :value="__('Name with Initial')" />
                        <x-text-input id="mother_name_with_initial" name="mother_name_with_initial" type="text" class="mt-1 block w-full" :value="old('mother_name_with_initial', $S->mother_name_with_initial ?? '')" />
                    </div>
                    <div>
                        <x-input-label for="mother_nic_passport" :value="__('NIC / Passport')" />
                        <x-text-input id="mother_nic_passport" name="mother_nic_passport" type="text" class="mt-1 block w-full" :value="old('mother_nic_passport', $S->mother_nic_passport ?? '')" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="mother_religion" :value="__('Religion')" />
                            <x-text-input id="mother_religion" name="mother_religion" type="text" class="mt-1 block w-full" :value="old('mother_religion', $S->mother_religion ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="mother_nationality" :value="__('Nationality')" />
                            <x-text-input id="mother_nationality" name="mother_nationality" type="text" class="mt-1 block w-full" :value="old('mother_nationality', $S->mother_nationality ?? '')" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="mother_occupation" :value="__('Occupation')" />
                        <x-text-input id="mother_occupation" name="mother_occupation" type="text" class="mt-1 block w-full" :value="old('mother_occupation', $S->mother_occupation ?? '')" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="mother_phone" :value="__('Mobile')" />
                            <x-text-input id="mother_phone" name="mother_phone" type="text" class="mt-1 block w-full" :value="old('mother_phone', $S->mother_phone ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="mother_whatsapp" :value="__('WhatsApp')" />
                            <x-text-input id="mother_whatsapp" name="mother_whatsapp" type="text" class="mt-1 block w-full" :value="old('mother_whatsapp', $S->mother_whatsapp ?? '')" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="mother_office_phone" :value="__('Office Phone')" />
                            <x-text-input id="mother_office_phone" name="mother_office_phone" type="text" class="mt-1 block w-full" :value="old('mother_office_phone', $S->mother_office_phone ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="mother_emergency_number" :value="__('Emergency')" />
                            <x-text-input id="mother_emergency_number" name="mother_emergency_number" type="text" class="mt-1 block w-full" :value="old('mother_emergency_number', $S->mother_emergency_number ?? '')" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Guardian Form (Shown if useGuardian is true) -->
        <div x-show="useGuardian" class="bg-orange-50 p-5 rounded-xl border border-orange-100 mt-4">
            <h4 class="font-bold text-gray-700 mb-4 flex items-center">
                <span class="w-2 h-2 bg-orange-500 rounded-full mr-2"></span> Guardian's Information
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="guardian_name">
                        {{ __('Guardian Name') }} <span class="text-red-600">*</span>
                    </x-input-label>
                    <x-text-input id="guardian_name" name="guardian_name" type="text" class="mt-1 block w-full"
                        :value="old('guardian_name', $S->guardian_name ?? '')" />
                </div>
                <div>
                    <x-input-label for="guardian_relationship">
                        {{ __('Relationship') }} <span class="text-red-600">*</span>
                    </x-input-label>
                    <x-text-input id="guardian_relationship" name="guardian_relationship" type="text" class="mt-1 block w-full"
                        :value="old('guardian_relationship', $S->guardian_relationship ?? '')" placeholder="e.g. Uncle, Grandparent" />
                </div>
                <div>
                    <x-input-label for="guardian_phone">
                        {{ __('Guardian Phone') }} <span class="text-red-600">*</span>
                    </x-input-label>
                    <x-text-input id="guardian_phone" name="guardian_phone" type="text" class="mt-1 block w-full"
                        :value="old('guardian_phone', $S->guardian_phone ?? '')" />
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Details Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center mb-6 pb-2 border-b border-gray-100">
            <div class="bg-green-100 p-2 rounded-lg mr-3">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-800">Contact Details</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <x-input-label for="address" :value="__('Current Address')" />
                <textarea id="address" name="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('address', $S->address ?? '') }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('address')" />
            </div>
            <div class="md:col-span-2">
                <x-input-label for="parent_address">
                    {{ __('Permanent Address') }} {!! $star('parent_address') !!}
                </x-input-label>
                <x-text-input id="parent_address" name="parent_address" type="text" class="mt-1 block w-full"
                    :value="old('parent_address', $S->parent_address ?? '')" required />
                <x-input-error class="mt-2" :messages="$errors->get('parent_address')" />
            </div>
            
            <div>
                <x-input-label for="phone" :value="__('Student Phone')" />
                <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full"
                    :value="old('phone', $S->phone ?? '')" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>
            <div>
                <x-input-label for="whatsapp_number" :value="__('Student WhatsApp Number')" />
                <x-text-input id="whatsapp_number" name="whatsapp_number" type="tel" class="mt-1 block w-full"
                    :value="old('whatsapp_number', $S->whatsapp_number ?? '')" placeholder="e.g. +94..." />
                <x-input-error class="mt-2" :messages="$errors->get('whatsapp_number')" />
            </div>
        </div>
    </div>
</div>
