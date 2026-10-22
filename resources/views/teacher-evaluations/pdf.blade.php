<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Teacher evaluations</title>
    <style>@page { margin: 26px 30px 32px; } body { margin: 0; } .export-break { page-break-before: always; } .instructions { font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.5; } .instructions li { margin-bottom: 12px; } .instructions h1 { background: #29166f; color: white; padding: 12px 15px; font-size: 18px; } .filter-list { font-family: DejaVu Sans, sans-serif; font-size: 10px; } </style>
    @include('teacher-evaluations.report-style')
</head><body>
    @if(!$blank && count($filters))<div class="filter-list"><strong>Export filters:</strong> {{ implode(' | ', array_map(fn($key, $value) => str_replace('_', ' ', $key).': '.$value, array_keys(array_filter($filters, fn($v) => $v !== null && $v !== '')), array_values(array_filter($filters, fn($v) => $v !== null && $v !== '')))) }}</div>@endif
    @forelse($evaluations as $evaluation)<div @if(!$loop->first)class="export-break"@endif>@include('teacher-evaluations.report', ['evaluation' => $evaluation, 'showScoreSummary' => true])</div>@empty<div class="evaluation-report"><h1>{{ $schoolName }}</h1><h2>No evaluations match these filters.</h2><p>Adjust the filters and export again.</p></div>@endforelse
    @if($blank)<div class="export-break instructions"><h1>How to complete this form</h1><ol>@foreach(\App\Services\TeacherEvaluations\EvaluationForm::instructions() as $instruction)<li>{{ $instruction }}</li>@endforeach</ol></div>@endif
</body></html>
