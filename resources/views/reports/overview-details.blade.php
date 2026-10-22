@php
    $payrollLabel = isset($filters['payroll_month']) ? \Carbon\Carbon::createFromFormat('!Y-m', $filters['payroll_month'])->format('F Y') : today()->format('F Y');
    $isFee = in_array($metric, ['exam', 'facility'], true);
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div><h2 class="text-2xl font-bold text-gray-900">{{ $settings['title'] }}</h2><p class="mt-1 text-sm text-gray-600">{{ $metric === 'teachers' ? 'Unsettled salary for '.$payrollLabel.'.' : $settings['note'] }}</p></div>
            <a class="text-sm font-semibold text-indigo-600" href="{{ route('reports.index', \Illuminate\Support\Arr::only($filters, ['period', 'from', 'to'])) }}#overview">← Back to overview</a>
        </div>
    </x-slot>
    <div class="mx-auto max-w-7xl py-8">
        @if($errors->any())<div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <section class="report-overview">
            <div class="report-overview-heading">
                <div>
                    <span class="report-overview-eyebrow">{{ $settings['scope'] === 'period' ? 'SELECTED DATE RANGE' : 'CURRENT OUTSTANDING BALANCE' }}</span>
                    <h2>{{ $settings['scope'] === 'period' ? \Carbon\Carbon::parse($filters['from'])->format('d M Y').' - '.\Carbon\Carbon::parse($filters['to'])->format('d M Y') : 'As of '.today()->format('d M Y') }}</h2>
                    <p>{{ $settings['scope'] === 'pending' ? match ($metric) { 'teachers' => 'Salary remaining for '.$payrollLabel.'. Settlements are deducted. Expected salary uses the teacher’s current salary setting.', 'students' => 'Current monthly fee balances, after payments, credits and held amounts. Exam and facility fees are separate.', default => 'Installments due today or earlier. Filter by the grade at the due date, year and payment status. Future fees are excluded.' } : 'Only records included in this overview total are listed below.' }}</p>
                </div>
                <div class="report-overview-detail-total"><span>Matching total · {{ number_format($items->total()) }} {{ \Illuminate\Support\Str::plural('record', $items->total()) }}</span><strong>Rs {{ number_format($filteredTotal, 2) }}</strong></div>
            </div>
            @if($settings['scope'] === 'period')
                @include('reports.partials.overview-filters', ['overviewFilters' => $filters, 'filterAction' => route('reports.overview.details', $metric), 'showSearch' => true])
            @else
                @include('reports.partials.pending-filters')
            @endif
            @if($items->total() !== $totalCount)
                <p class="report-overview-footnote">Showing {{ number_format($items->total()) }} of {{ number_format($totalCount) }} records. Total before filters: <strong>Rs {{ number_format($total, 2) }}</strong>. The matching total includes every result across all pages.</p>
            @endif
            <div class="report-overview-table-scroll" role="region" aria-label="{{ $settings['title'] }} details" tabindex="0">
                <table class="report-overview-table">
                    <thead><tr><th>{{ $settings['scope'] === 'pending' ? 'Due / as of' : 'Date' }}</th>@if($isFee)<th>{{ $metric === 'exam' ? 'Exam session / year' : 'Installment year' }}</th>@endif<th>Reference</th><th>Student / teacher / category</th><th>Description</th><th>Status</th><th class="is-amount">Amount (Rs)</th></tr></thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $item['date'] ? \Carbon\Carbon::parse($item['date'])->format('d M Y') : '—' }}</td>
                                @if($isFee)<td>@if($metric === 'exam')<strong>Session {{ $item['exam_session'] }}</strong><br>@endif{{ $item['installment_year'] }}</td>@endif
                                <td>{{ $item['reference'] }}</td><td class="is-name">{{ $item['party'] }}</td><td>{{ $item['description'] }}</td><td><span class="report-overview-status">{{ $item['status'] }}</span></td><td class="is-amount">{{ number_format($item['amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $isFee ? 7 : 6 }}" class="report-overview-empty">No records match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $items->links() }}</div>
        </section>
    </div>
</x-app-layout>
