@once
    @php
        // A partial upload must not crash the page. The asset still needs to be
        // placed in public/js, but versioning must tolerate its absence.
        $classFeeScriptPath = public_path('js/class-fees.js');
        $classFeeScriptVersion = is_file($classFeeScriptPath) ? filemtime($classFeeScriptPath) : '20261022';
    @endphp
    {{-- Load before the deferred Alpine bundle, including on older app builds. --}}
    <script src="{{ asset('js/class-fees.js') }}?v={{ $classFeeScriptVersion }}"></script>
@endonce
