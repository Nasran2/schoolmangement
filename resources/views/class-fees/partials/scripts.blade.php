@once
    {{-- This classic script runs while parsing, before the deferred Alpine bundle.
         Loading here keeps fee forms functional when the hosted app bundle is older. --}}
    <script src="{{ asset('js/class-fees.js') }}?v={{ filemtime(public_path('js/class-fees.js')) }}"></script>
@endonce
