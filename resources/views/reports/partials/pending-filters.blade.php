<form method="GET" class="report-overview-filters report-pending-filters" aria-label="Filter outstanding balances">
    @foreach(\Illuminate\Support\Arr::only($filters, ['period', 'from', 'to']) as $key => $value)<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
    @if(in_array($metric, ['students', 'exam', 'facility'], true))
        <div class="report-overview-filter">
            <label for="pending-class">Grade / class</label>
            <select id="pending-class" name="class_room_id"><option value="">All grades / classes</option>@foreach($filterOptions['classes'] as $class)<option value="{{ $class->id }}" @selected((string)($filters['class_room_id'] ?? '') === (string)$class->id)>{{ $class->name }}</option>@endforeach</select>
        </div>
    @endif
    @if(in_array($metric, ['exam', 'facility'], true))
        <div class="report-overview-filter">
            <label for="pending-year">Installment year</label>
            <select id="pending-year" name="installment_year"><option value="">All years (earlier dues included)</option>@foreach($filterOptions['years'] as $year)<option value="{{ $year }}" @selected((string)($filters['installment_year'] ?? '') === (string)$year)>{{ $year }}</option>@endforeach</select>
        </div>
    @endif
    @if($metric === 'teachers')
        <div class="report-overview-filter"><label for="pending-month">Payroll month</label><input id="pending-month" type="month" name="payroll_month" value="{{ $filters['payroll_month'] }}" max="{{ today()->format('Y-m') }}" required></div>
        <div class="report-overview-filter"><label for="pending-teacher">Teacher</label><select id="pending-teacher" name="teacher_id"><option value="">All active teachers</option>@foreach($filterOptions['teachers'] as $teacher)<option value="{{ $teacher->id }}" @selected((string)($filters['teacher_id'] ?? '') === (string)$teacher->id)>{{ $teacher->name }}</option>@endforeach</select></div>
    @endif
    @if(in_array($metric, ['teachers', 'exam', 'facility'], true))
        <div class="report-overview-filter"><label for="pending-status">Payment status</label><select id="pending-status" name="balance_status"><option value="">All outstanding</option><option value="unpaid" @selected(($filters['balance_status'] ?? '') === 'unpaid')>Unpaid</option><option value="partial" @selected(($filters['balance_status'] ?? '') === 'partial')>Partially paid</option>@if($metric !== 'teachers')<option value="pending" @selected(($filters['balance_status'] ?? '') === 'pending')>Pending cheque</option>@endif</select></div>
    @endif
    <div class="report-overview-filter"><label for="pending-minimum">Minimum balance (Rs)</label><input id="pending-minimum" type="number" name="min_amount" value="{{ $filters['min_amount'] ?? '' }}" min="0" max="999999999999" step="0.01" placeholder="Any amount"></div>
    <div class="report-overview-filter report-overview-search"><label for="overview-search">Search details</label><input type="search" id="overview-search" name="q" value="{{ $filters['q'] }}" placeholder="Name, reference or class"></div>
    <div class="report-overview-filter"><label for="pending-sort">Sort by</label><select id="pending-sort" name="sort">@foreach(['default' => 'Default order', 'amount_desc' => 'Highest balance first', 'amount_asc' => 'Lowest balance first', 'name_asc' => 'Name A–Z'] + (in_array($metric, ['exam', 'facility'], true) ? ['date_asc' => 'Oldest due date first', 'date_desc' => 'Latest due date first'] : []) as $value => $label)<option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>@endforeach</select></div>
    @if($metric === 'exam')
        <fieldset class="report-session-filter"><legend>Exam sessions</legend><input type="hidden" name="sessions_selected" value="1"><div class="report-session-options">@foreach(range(1, $filterOptions['session_count']) as $session)<label><input type="checkbox" name="exam_sessions[]" value="{{ $session }}" @checked(in_array($session, $filters['exam_sessions'], true))><span>Session {{ $session }}</span></label>@endforeach</div><p>Sessions follow scheduled due dates within each grade and year. All sessions are selected by default.</p></fieldset>
    @endif
    <div class="report-overview-filter-actions report-pending-actions"><button type="submit">Apply filters</button><a href="{{ route('reports.overview.details', array_merge(['metric' => $metric], \Illuminate\Support\Arr::only($filters, ['period', 'from', 'to']))) }}">Clear filters</a></div>
</form>
