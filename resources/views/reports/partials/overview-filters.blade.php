<form method="GET" action="{{ $filterAction }}" class="report-overview-filters" x-data="{ period: @js($overviewFilters['period']) }" aria-label="Overview date filters">
    <div class="report-overview-filter">
        <label for="overview-period">Date range</label>
        <select id="overview-period" name="period" x-model="period">
            @foreach(['today' => 'Today', 'yesterday' => 'Yesterday', 'this_week' => 'This week to date', 'this_month' => 'This month to date', 'last_month' => 'Last month', 'custom' => 'Custom dates'] as $value => $label)
                <option value="{{ $value }}" @selected($overviewFilters['period'] === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="report-overview-filter" x-show="period === 'custom'" @if($overviewFilters['period'] !== 'custom') x-cloak @endif>
        <label for="overview-from">From</label><input id="overview-from" type="date" name="from" value="{{ $overviewFilters['from'] }}">
    </div>
    <div class="report-overview-filter" x-show="period === 'custom'" @if($overviewFilters['period'] !== 'custom') x-cloak @endif>
        <label for="overview-to">To</label><input id="overview-to" type="date" name="to" value="{{ $overviewFilters['to'] }}">
    </div>
    @if($showSearch ?? false)
        <div class="report-overview-filter report-overview-search"><label for="overview-search">Search details</label><input type="search" id="overview-search" name="q" value="{{ $overviewFilters['q'] }}" placeholder="Name, reference or description"></div>
    @endif
    <div class="report-overview-filter-actions"><button type="submit">Apply filters</button><a href="{{ $filterAction }}">Today</a></div>
</form>
