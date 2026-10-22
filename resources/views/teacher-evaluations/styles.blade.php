@php($evaluationStylesPath = resource_path('css/teacher-evaluations.css'))
{{-- Keep these screens styled when a shared-hosting upload has an older Vite build.
     The source stylesheet is shared with Vite; no separate copy needs maintaining. --}}
@if(is_readable($evaluationStylesPath))
    <style id="teacher-evaluation-styles">
        {!! file_get_contents($evaluationStylesPath) !!}
    </style>
@endif
