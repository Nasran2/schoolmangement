@php
    $directoryRoute = $isStudent ? 'hr.students.index' : 'hr.teachers.index';
    $initials = collect(explode(' ', trim(preg_replace('/^\[SAMPLE\]\s*/', '', $person->name))))->filter()->take(2)->map(fn($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">{{ $isStudent ? 'Students' : 'Teachers' }}</p><h2 class="mt-1 text-2xl font-semibold text-gray-900">{{ $isStudent ? 'Student profile' : 'Teacher profile' }}</h2></div>
            <a href="{{ route($directoryRoute) }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50"><span aria-hidden="true">&larr;</span> {{ $isStudent ? 'All students' : 'All teachers' }}</a>
        </div>
    </x-slot>
    @if(!$isStudent)
        @include('hr.teacher-profile')
    @else
    <div class="mx-auto max-w-7xl space-y-6 py-7">
        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-5">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-xl font-bold text-indigo-700">{{ $initials }}</div>
                    <div class="min-w-0"><h1 class="break-words text-xl font-bold text-gray-900">{{ $person->name }}</h1><p class="mt-1 text-sm text-gray-500">{{ $isStudent ? $person->admission_number : ($person->assigned_classes ?: 'No class assigned') }}@if($isStudent) <span class="mx-1.5 text-gray-300">/</span> {{ $person->classRoom?->name ?? $person->class ?? 'No class assigned' }}@endif</p></div>
                </div>
                <div class="flex flex-wrap items-center gap-2"><span class="rounded-full px-3 py-1 text-xs font-semibold {{ $person->active ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-gray-100 text-gray-600 ring-1 ring-gray-200' }}">{{ $person->active ? 'Active' : 'Inactive' }}</span>@if($isStudent && $person->alumni)<span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Alumni</span>@endif<span class="text-xs text-gray-400">Details are read-only</span></div>
            </div>
        </section>
            <div class="grid items-start gap-6 lg:grid-cols-3">
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2"><h3 class="text-base font-semibold text-gray-900">Student information</h3><p class="mt-1 text-sm text-gray-500">Admission, class and personal details.</p><dl class="mt-6 grid gap-x-8 gap-y-6 sm:grid-cols-2"><x-profile-value label="Admission number" :value="$person->admission_number"/><x-profile-value label="Name with initials" :value="$person->name_with_initial"/><x-profile-value label="Class" :value="$person->classRoom?->name ?? $person->class"/><x-profile-value label="Joining date" :value="$person->joining_date"/><x-profile-value label="Gender" :value="$person->gender"/><x-profile-value label="Date of birth" :value="$person->date_of_birth"/></dl></section>
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm"><h3 class="font-semibold text-gray-900">Contact details</h3><dl class="mt-6 space-y-5"><x-profile-value label="Phone" :value="$person->phone"/><x-profile-value label="WhatsApp" :value="$person->whatsapp_number"/><x-profile-value label="Address" :value="$person->address"/></dl></section>
                <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-3"><h3 class="font-semibold text-gray-900">Parents and guardian</h3><dl class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"><x-profile-value label="Guardian" :value="$person->guardian_name"/><x-profile-value label="Relationship" :value="$person->guardian_relationship"/><x-profile-value label="Guardian phone" :value="$person->guardian_phone"/><x-profile-value label="Father" :value="$person->father_name_with_initial"/><x-profile-value label="Father phone" :value="$person->father_phone"/><div class="hidden lg:block"></div><x-profile-value label="Mother" :value="$person->mother_name_with_initial"/><x-profile-value label="Mother phone" :value="$person->mother_phone"/></dl></section>
            </div>
    </div>
    @endif
</x-app-layout>
