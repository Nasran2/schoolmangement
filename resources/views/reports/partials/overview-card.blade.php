<a href="{{ $summaryCard['url'] }}" class="report-overview-card report-tone-{{ $summaryCard['tone'] }}" data-overview-card="{{ $summaryCard['key'] }}">
    <div class="report-overview-card-heading"><h4>{{ $summaryCard['title'] }}</h4><span class="report-overview-card-icon"><x-profile-icon :name="$summaryCard['scope'] === 'pending' ? 'clock' : 'document'" /></span></div>
    <p class="report-overview-amount"><span>Rs</span> {{ number_format($summaryCard['amount'], 2) }}</p>
    <p class="report-overview-count">{{ number_format($summaryCard['count']) }} {{ \Illuminate\Support\Str::plural(match ($summaryCard['key']) { 'teachers' => 'teacher', 'students' => 'student', 'exam', 'computer', 'facility' => 'installment', default => 'record' }, $summaryCard['count']) }}</p>
    <p class="report-overview-card-note" title="{{ $summaryCard['note'] }}">{{ match ($summaryCard['key']) { 'collection' => 'Student payments, after refunds', 'expense' => 'Expenses, salaries & teacher payouts', 'revenue' => 'All income, after refunds', 'teachers' => today()->format('F Y').' payroll', 'students' => 'Monthly fees only', default => 'Due today and earlier' } }}</p>
    <div class="report-overview-card-link">View complete details <x-profile-icon name="arrow" /></div>
</a>
