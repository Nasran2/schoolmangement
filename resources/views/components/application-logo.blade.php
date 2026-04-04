@php
    $logo = app('settings')->get('school.logo');
    $schoolName = (string) app('settings')->get('school.name', config('app.name', 'School'));
    $words = preg_split('/\s+/', trim($schoolName) ?: 'School') ?: [];
    $initials = collect($words)
        ->filter()
        ->take(2)
        ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
        ->implode('');
    $initials = $initials !== '' ? $initials : 'SC';
@endphp

@if($logo)
    <img src="{{ Storage::url($logo) }}" {{ $attributes }} alt="{{ $schoolName }} logo">
@else
    <div {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-md bg-gray-200 text-gray-700 font-semibold']) }} aria-label="{{ $schoolName }} logo">
        <span>{{ $initials }}</span>
    </div>
@endif
