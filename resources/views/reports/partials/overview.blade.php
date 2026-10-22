@if($overviewCards->isNotEmpty())
<section id="overview" class="report-overview" aria-labelledby="overview-title">
    <div class="report-overview-heading">
        <div><span class="report-overview-eyebrow">AT A GLANCE</span><h2 id="overview-title">Daily overview</h2><p>Collections, spending and outstanding payments in one place.</p></div>
        <div class="report-overview-date"><x-profile-icon name="calendar" />{{ \Carbon\Carbon::parse($overviewFilters['from'])->format('d M Y') }}@if($overviewFilters['from'] !== $overviewFilters['to']) - {{ \Carbon\Carbon::parse($overviewFilters['to'])->format('d M Y') }}@endif</div>
    </div>
    @include('reports.partials.overview-filters', ['filterAction' => route('reports.index').'#overview'])
    <div class="report-overview-section-title"><h3>For the selected dates</h3><span>Collection is included in total revenue.</span></div>
    <div class="report-overview-grid is-period">
        @foreach($overviewCards->where('scope', 'period') as $summaryCard)
            @include('reports.partials.overview-card')
        @endforeach
    </div>
    <div class="report-overview-section-title"><h3>Still pending today</h3><span>As of {{ today()->format('d M Y') }} · Includes earlier dues. Salaries: {{ today()->format('F Y') }}.</span></div>
    <div class="report-overview-grid is-pending">
        @foreach($overviewCards->where('scope', 'pending') as $summaryCard)
            @include('reports.partials.overview-card')
        @endforeach
    </div>
    <p class="report-overview-footnote">Date filters apply to collections, expenses and revenue. Pending cards show current outstanding balances for active students and teachers. Exam and facility fees are separate from monthly student receivables.</p>
</section>
@endif
