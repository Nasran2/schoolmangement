@php
    $annual = $evaluation->type === 'annual';
    $criteria = $evaluation->criteria ?? [];
    $ratings = old('ratings', collect($criteria)->mapWithKeys(fn($c) => [$c['id'] => \App\Services\TeacherEvaluations\EvaluationForm::ratings($c)[0] ?? ''])->all());
    $additionalRatings = [];
    foreach ([2, 3] as $supervisor) {
        $values = old('additional_ratings.'.$supervisor, collect($criteria)->mapWithKeys(fn($c) => [$c['id'] => \App\Services\TeacherEvaluations\EvaluationForm::ratings($c)[$supervisor - 1] ?? ''])->all());
        $additionalRatings[$supervisor] = is_array($values) ? $values : [];
    }
    $ratings = is_array($ratings) ? $ratings : [];
    $evidenceRows = old('evidence', $evaluation->evidence ?: [array_fill_keys(array_keys(\App\Services\TeacherEvaluations\EvaluationForm::EVIDENCE_FIELDS), '')]);
    $developmentRows = old('development', $evaluation->development ?: [array_fill_keys(array_keys(\App\Services\TeacherEvaluations\EvaluationForm::DEVELOPMENT_FIELDS), '')]);
@endphp
<x-app-layout>
    <x-slot name="header"><div class="flex justify-between gap-4"><div><h2 class="text-2xl font-semibold">{{ $evaluation->exists ? 'Edit' : 'New' }} {{ $annual ? 'Annual Appraisal' : 'Event Review' }}</h2><p class="mt-1 text-sm text-gray-600">Enter the available details from the physical form. Missing details can be saved after a warning.</p></div><a class="text-indigo-600 underline text-sm" href="{{ route('teacher-evaluations.instructions') }}" target="_blank">Instructions</a></div></x-slot>
    <div class="evaluation-form mx-auto max-w-screen-2xl py-8">
        <form method="POST" action="{{ $evaluation->exists ? route('teacher-evaluations.update', $evaluation) : route('teacher-evaluations.store') }}" class="evaluation-form-content space-y-6" x-ref="evaluationForm" @submit="reviewBeforeSave($event)" x-data="teacherEvaluationForm({ checklist: {{ \Illuminate\Support\Js::from(\App\Services\TeacherEvaluations\EvaluationForm::reviewChecklist($evaluation->type, $criteria)) }}, initialMissingDetails: {{ \Illuminate\Support\Js::from(session('evaluation_missing_details', [])) }}, pendingStatus: {{ \Illuminate\Support\Js::from(session('evaluation_pending_status', 'draft')) }}, ratings: {{ \Illuminate\Support\Js::from($ratings) }}, additionalRatings: {{ \Illuminate\Support\Js::from($additionalRatings) }}, weights: {{ \Illuminate\Support\Js::from(collect($criteria)->mapWithKeys(fn($c) => [$c['id'] => $c['weight']])->all()) }}, evidence: {{ \Illuminate\Support\Js::from($evidenceRows) }}, development: {{ \Illuminate\Support\Js::from($developmentRows) }} })">
            <input type="hidden" name="status" x-ref="pendingStatus" value="{{ session('evaluation_pending_status', 'draft') }}">
            <input type="hidden" name="acknowledge_incomplete" x-ref="acknowledgeInput" value="0">
            @include('teacher-evaluations.incomplete-prompt')
            @csrf @if($evaluation->exists)@method('PUT')@endif
            <input type="hidden" name="type" value="{{ $evaluation->type }}">
            @if($evaluation->exists)<input type="hidden" name="version" value="{{ old('version', $evaluation->version) }}">@endif
            @if($errors->any())<div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-5 text-red-700"><p class="font-semibold mb-2">Please correct these fields:</p><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
            <section class="evaluation-details-section rounded-xl border bg-white p-6 space-y-5"><h3 class="evaluation-section-label">Teacher and review details</h3>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="evaluation-teacher-picker">
                        <label for="teacher_id" class="block text-sm font-medium mb-1">Teacher *</label>
                        <select id="teacher_id" name="teacher_id" required data-searchable-select data-placeholder="Search teacher by name, ID or class" aria-describedby="teacher-search-help" class="w-full rounded-md border-gray-300" @if($evaluation->exists) disabled @endif>
                            <option value="">Search or select teacher</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected(old('teacher_id', $evaluation->teacher_id) == $teacher->id)>{{ $teacher->name }} · Teacher #{{ $teacher->id }}{{ $teacher->assigned_classes ? ' · '.$teacher->assigned_classes : '' }}{{ !$teacher->active ? ' (inactive)' : '' }}</option>
                            @endforeach
                        </select>
                        @if($evaluation->exists)
                            <input type="hidden" name="teacher_id" value="{{ $evaluation->teacher_id }}">
                            <p id="teacher-search-help" class="mt-2 text-xs text-gray-500">The teacher is fixed for this evaluation.</p>
                        @else
                            <p id="teacher-search-help" class="mt-2 text-xs text-gray-500">Type a name, teacher ID or assigned class to see matching suggestions.</p>
                        @endif
                    </div>
                    @if($annual)<x-evaluation-field name="appraisal_year" type="number" label="Appraisal year *" :value="$evaluation->appraisal_year" min="2000" max="2100" required />@else<x-evaluation-field name="event_name" label="Event / function name *" :value="$evaluation->event_name" maxlength="191" required />@endif
                    <x-evaluation-field name="staff_id" label="Staff ID (if used)" :value="$evaluation->staff_id" maxlength="80" />
                    <div><label for="section" class="block text-sm font-medium mb-1">Section</label><select id="section" name="section" class="w-full rounded-md border-gray-300"><option value="">Select section</option>@foreach(\App\Services\TeacherEvaluations\EvaluationForm::SECTIONS as $section)<option @selected(old('section', $evaluation->section) === $section)>{{ $section }}</option>@endforeach</select></div>
                    <x-evaluation-field name="subject_class" label="Subject / class" :value="$evaluation->subject_class" maxlength="191" />
                    <x-evaluation-field name="evaluator" label="Evaluator / authorised reviewer" :value="$evaluation->evaluator" maxlength="191" />
                    <x-evaluation-field name="review_date" type="date" label="Review date" :value="$evaluation->review_date?->format('Y-m-d')" />
                    @if($annual)
                        <x-evaluation-field name="period_start" type="date" label="Appraisal period from" :value="$evaluation->period_start?->format('Y-m-d')" />
                        <x-evaluation-field name="period_end" type="date" label="Appraisal period to" :value="$evaluation->period_end?->format('Y-m-d')" />
                        <x-evaluation-field name="years_of_service" label="Years of service" :value="$evaluation->years_of_service" maxlength="80" />
                    @else<x-evaluation-field name="event_date" type="date" label="Event date" :value="$evaluation->event_date?->format('Y-m-d')" />@endif
                </div>
                @if(!$annual)<x-evaluation-field name="responsibility" type="textarea" label="Assigned responsibility / duties" :value="$evaluation->responsibility" maxlength="10000" />@endif
                <p class="text-xs text-gray-500">Teacher and appraisal year or event name identify the report. Other review details may be left blank. Saving shows a warning for incomplete details, with an option to save anyway.</p>
            </section>
            @if($annual)
                @include('teacher-evaluations.annual-ratings')
            @endif
            @foreach(['evidence' => ['title' => 'Evidence and examples', 'fields' => \App\Services\TeacherEvaluations\EvaluationForm::EVIDENCE_FIELDS], 'development' => ['title' => 'Professional development action plan', 'fields' => \App\Services\TeacherEvaluations\EvaluationForm::DEVELOPMENT_FIELDS]] as $group => $settings)
                @include('teacher-evaluations.entry-table')
            @endforeach
            <section class="evaluation-details-section rounded-xl border bg-white p-6 space-y-5"><h3 class="evaluation-section-label">Comments and physical signatures</h3>
                <x-evaluation-field name="teacher_comments" type="textarea" label="Teacher's comments" :value="$evaluation->teacher_comments" rows="4" maxlength="10000" />
                <x-evaluation-field name="management_comments" type="textarea" label="Management decision / comments (as recorded on the physical form)" :value="$evaluation->management_comments" rows="4" maxlength="10000" />
                <p class="text-sm text-gray-500">Enter names and dates only if the physical form was signed. These details record the original signature and do not create a digital signature.</p>
                <div class="grid gap-5 sm:grid-cols-2"><x-evaluation-field name="teacher_signed_name" label="Teacher signature name" :value="$evaluation->teacher_signed_name" maxlength="191" /><x-evaluation-field name="teacher_signed_date" type="date" label="Teacher signature date" :value="$evaluation->teacher_signed_date?->format('Y-m-d')" /><x-evaluation-field name="evaluator_signed_name" label="Evaluator signature name" :value="$evaluation->evaluator_signed_name" maxlength="191" /><x-evaluation-field name="evaluator_signed_date" type="date" label="Evaluator signature date" :value="$evaluation->evaluator_signed_date?->format('Y-m-d')" /></div>
            </section>
            <section class="evaluation-details-section rounded-xl border bg-white p-6 space-y-5"><h3 class="evaluation-section-label">Follow-up review</h3><p class="text-sm text-gray-500">Leave the completion date blank until all actions in this report have been reviewed.</p><div class="grid gap-5 sm:grid-cols-2"><x-evaluation-field name="follow_up_completed_date" type="date" label="Follow-up completed date" :value="$evaluation->follow_up_completed_date?->format('Y-m-d')" /><x-evaluation-field name="follow_up_notes" type="textarea" label="Follow-up review notes" :value="$evaluation->follow_up_notes" maxlength="10000" /></div></section>
            <div class="rounded-xl border bg-white p-5 flex flex-wrap items-center gap-4"><button type="submit" name="status" value="draft" :disabled="saving" class="rounded-md border px-5 py-3 font-semibold text-gray-700">Save draft</button><button type="submit" name="status" value="completed" :disabled="saving" class="rounded-md bg-indigo-600 px-5 py-3 font-semibold text-white">Save completed report</button><a class="text-gray-600 underline" href="{{ $evaluation->exists ? route('teacher-evaluations.show', $evaluation) : route('teacher-evaluations.index') }}">Cancel</a></div>
        </form>
    </div>
</x-app-layout>
