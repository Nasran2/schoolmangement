<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap justify-between gap-4"><div><h2 class="text-2xl font-semibold">{{ $evaluation->teacher_name }} - {{ $evaluation->type_label }}</h2><p class="text-sm text-gray-600 mt-1">{{ ucfirst($evaluation->status) }} · Record #{{ $evaluation->id }}</p></div><div class="flex flex-wrap items-center gap-4 text-sm">@can('hr.directory.view')<a class="rounded-lg border border-gray-200 bg-white px-4 py-2 font-medium text-gray-700" href="{{ route('hr.teachers.show', $evaluation->teacher_id) }}">Teacher profile</a>@endcan<a class="text-indigo-600 underline" href="{{ route('teacher-evaluations.index') }}">Back to evaluations</a></div></div></x-slot>
    <div class="max-w-6xl mx-auto px-4 py-8 space-y-6">
        @if(session('status'))<p class="bg-green-50 rounded-lg p-4 text-green-800">{{ session('status') }}</p>@endif
        @if($evaluation->type === 'annual')
            @include('teacher-evaluations.score-summary')
        @endif
        @php($missingReviewDetails = \App\Services\TeacherEvaluations\EvaluationForm::missingDetails($evaluation))
        @if(count($missingReviewDetails))
            <div class="evaluation-incomplete-notice"><details><summary>Some review details have not been entered ({{ count($missingReviewDetails) }})</summary><ul>@foreach($missingReviewDetails as $missingReviewField)<li>{{ $missingReviewField }}</li>@endforeach</ul></details></div>
        @endif
        <div class="flex flex-wrap items-center gap-4">@can('teacher_evaluations.manage')<a class="rounded-md bg-indigo-600 text-white px-4 py-2" href="{{ route('teacher-evaluations.edit', $evaluation) }}">Edit evaluation</a>@endcan @can('teacher_evaluations.export')<a class="rounded-md border bg-white px-4 py-2" href="{{ route('teacher-evaluations.download', ['evaluation' => $evaluation, 'format' => 'pdf']) }}">Download PDF</a><a class="rounded-md border bg-white px-4 py-2" href="{{ route('teacher-evaluations.download', ['evaluation' => $evaluation, 'format' => 'xlsx']) }}">Download Excel</a>@endcan</div>
        <p class="text-sm text-gray-500">Entered by {{ $evaluation->creator?->name ?? 'Former user' }}. Last saved by {{ $evaluation->editor?->name ?? 'Former user' }} on {{ $evaluation->updated_at->format('d M Y H:i') }}. Version {{ $evaluation->version }}.</p>
        <div class="evaluation-preview bg-white border rounded-xl p-6 overflow-x-auto">@include('teacher-evaluations.report-style')<div style="min-width:960px">@include('teacher-evaluations.report', ['blank' => false])</div></div>
        @can('teacher_evaluations.delete')<form method="POST" action="{{ route('teacher-evaluations.destroy', $evaluation) }}" onsubmit="return confirm('Delete this evaluation? This removes the evaluation from the teacher history.');">@csrf @method('DELETE')<button type="submit" class="text-sm text-red-600 underline">Delete evaluation</button></form>@endcan
    </div>
</x-app-layout>
