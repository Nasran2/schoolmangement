<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-xl font-semibold text-gray-900">{{ $reportTitle }} — All students</h2>        <nav class="fee-register-view-tabs" aria-label="Fee register views">
            <a class="text-indigo-700 underline" href="{{ route('class-fees.index', array_replace($registerQuery, ['view' => 'overview'])) }}">Overview</a>
            <span class="font-semibold text-gray-600" aria-current="page">All students</span>
            <a class="text-indigo-700 underline" href="{{ route('class-fees.index', array_replace($registerQuery, ['type' => $type === 'exam' ? 'facility' : 'exam', 'view' => 'students'])) }}">{{ $type === 'exam' ? 'Facility fees only' : 'Exam & facility fees' }}</a>
            <a class="text-indigo-700 underline" href="{{ route('class-fees.index', array_replace($registerQuery, ['type' => 'computer', 'view' => 'students'])) }}">Computer fees only</a>
        </nav></div>
            <div class="flex flex-wrap gap-2">
                @can('revenue.add')<a href="{{ route('class-fees.bulk', ['year' => $year, 'class_room_id' => $class?->id]) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Bulk payments</a>@endcan
                @can('revenue.delete')<a href="{{ route('class-fees.bulk', ['mode' => 'unpaid', 'year' => $year, 'class_room_id' => $class?->id]) }}" class="rounded-md border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-red-700">Bulk make unpaid</a>@endcan
            </div>
        </div>
    </x-slot>
    @php($registerAsset = public_path('js/fee-register.js'))
    <script src="{{ asset('js/fee-register.js') }}?v={{ is_file($registerAsset) ? filemtime($registerAsset) : '1' }}" defer></script>
    @include('class-fees.partials.students-styles')
    <div class="fee-register-compact" data-fee-register>

        @if(session('status'))<div role="status" class="rounded-lg bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>@endif
        <div data-register-notice hidden class="fee-register-notice" role="status"></div>
        @include('class-fees.partials.filters')
        <div class="fee-register-loading" role="status" aria-live="polite" data-register-status>Search updates as you type.</div>
        <div data-register-results class="fee-register-results">@include('class-fees.partials.students-results')</div>
        <dialog class="fee-register-dialog" data-register-dialog aria-labelledby="fee-register-dialog-title">
            <div class="fee-register-dialog-header"><h2 id="fee-register-dialog-title">Student fee</h2><button type="button" data-register-close class="fee-register-button" aria-label="Close payment dialog">Close</button></div>
            <div data-register-panel></div>
        </dialog>
    </div>
</x-app-layout>
