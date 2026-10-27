@once
    @php
        // Read the application copy so a stale public/CDN copy or a custom
        // document root cannot serve factories that do not match these views.
        $classFeeScriptPath = base_path('public/js/class-fees.js');
        $classFeeScriptSource = is_readable($classFeeScriptPath) ? file_get_contents($classFeeScriptPath) : false;
    @endphp
    @if($classFeeScriptSource !== false)
        {{-- Trusted application source, executed before the deferred Alpine bundle. --}}
        <script data-class-fee-runtime="{{ hash('sha256', $classFeeScriptSource) }}">{!! $classFeeScriptSource !!}</script>
    @else
        <div role="alert" class="my-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            The fee form could not load. Please upload public/js/class-fees.js from the update ZIP, then refresh this page.
        </div>
    @endif
@endonce
