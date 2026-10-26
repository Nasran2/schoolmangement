@php
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $pageNumbers = collect([1, $lastPage])->merge(range(max(1, $currentPage - 2), min($lastPage, $currentPage + 2)))->unique()->sort()->values();
    $previousNumber = null;
@endphp
<div class="fee-register-pagination">
    <span class="fee-register-page-summary">Showing {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} class entries · {{ $studentCount }} students</span>
    <nav class="fee-register-page-links" aria-label="Student pages ({{ $position }})">
        <span class="fee-register-page-summary">Page {{ $currentPage }} of {{ $lastPage }}</span>
        @if($paginator->onFirstPage())
            <span class="fee-register-button fee-register-page-disabled" aria-disabled="true">First</span>
            <span class="fee-register-button fee-register-page-disabled" aria-disabled="true">Previous</span>
        @else
            <a class="fee-register-button" data-register-page href="{{ $paginator->url(1) }}">First</a>
            <a class="fee-register-button" data-register-page rel="prev" href="{{ $paginator->previousPageUrl() }}">Previous</a>
        @endif
        @foreach($pageNumbers as $number)
            @if($previousNumber !== null && $number > $previousNumber + 1)<span class="fee-register-page-gap" aria-hidden="true">…</span>@endif
            @if($number === $currentPage)
                <span class="fee-register-button fee-register-page-current" aria-current="page" aria-label="Page {{ $number }}">{{ $number }}</span>
            @else
                <a class="fee-register-button" data-register-page aria-label="Page {{ $number }}" href="{{ $paginator->url($number) }}">{{ $number }}</a>
            @endif
            @php($previousNumber = $number)
        @endforeach
        @if($paginator->hasMorePages())
            <a class="fee-register-button" data-register-page rel="next" href="{{ $paginator->nextPageUrl() }}">Next</a>
            <a class="fee-register-button" data-register-page href="{{ $paginator->url($lastPage) }}">Last</a>
        @else
            <span class="fee-register-button fee-register-page-disabled" aria-disabled="true">Next</span>
            <span class="fee-register-button fee-register-page-disabled" aria-disabled="true">Last</span>
        @endif
    </nav>
</div>
