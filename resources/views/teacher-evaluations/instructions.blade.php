<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-semibold">Teacher Evaluation Instructions</h2></x-slot>
    <div class="max-w-4xl mx-auto px-4 py-8 space-y-6"><a class="text-indigo-600 underline" href="{{ route('teacher-evaluations.index') }}">Back to evaluations</a><ol class="bg-white border rounded-xl p-8 list-decimal space-y-5 list-inside">@foreach(\App\Services\TeacherEvaluations\EvaluationForm::instructions() as $instruction)<li>{{ $instruction }}</li>@endforeach</ol></div>
</x-app-layout>
