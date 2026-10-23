<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }} - Student Payment List</title>
    <style>
        @page { margin: 26pt 26pt 38pt; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #172033; line-height: 1.3; }
        h1 { margin: 3px 0 5px; font-size: 21px; }
        .school { font-size: 12px; font-weight: bold; color: #4338ca; }
        .muted { color: #626d7d; }
        .header { border-bottom: 2px solid #4338ca; padding-bottom: 12px; margin-bottom: 12px; }
        .filters { background: #f4f5fa; border: 1px solid #dce0ea; padding: 9px 11px; margin-bottom: 12px; }
        .filters div { margin-bottom: 3px; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .summary td { width: 25%; padding: 8px 10px; border: 1px solid #dce0ea; }
        .value { display: block; margin-top: 3px; font-size: 14px; font-weight: bold; }
        .fees { width: 100%; table-layout: fixed; border-collapse: collapse; margin: 12px 0 6px; }
        .fees thead { display: table-header-group; }
        .fees thead, .fees thead tr { page-break-after: avoid; }
        .fees .first-row { page-break-before: avoid; }
        .fees tr { page-break-inside: avoid; }
        .fees th, .fees td { border: 1px solid #dce0ea; padding: 5px 6px; vertical-align: top; word-wrap: break-word; }
        .fees th { text-align: left; background: #f0f2f7; font-size: 8pt; }
        .class-name { font-size: 10pt; color: #4338ca; margin-bottom: 4px; }
        .fees tbody tr:nth-child(even) { background: #fafbfc; }
        .right { text-align: right !important; }
        .center { text-align: center !important; }
        .name, .status { font-weight: bold; }
        .sub { font-size: 7.5pt; color: #626d7d; margin-top: 2px; }
        .paid { color: #167449; }
        .unpaid { color: #b42332; }
        .pending { color: #946111; }
        .section-totals { text-align: right; font-weight: bold; margin: 6px 0 15px; }
        .note { font-size: 7.5pt; color: #626d7d; margin-top: 10px; }
        .empty { border: 1px solid #dce0ea; padding: 18px; text-align: center; color: #626d7d; }
    </style>
</head>
<body>
    @php
        $statusLabel = ['all' => 'Complete student list', 'unpaid' => 'Unpaid / balance due', 'paid' => 'Paid / all selected due installments settled', 'partial' => 'Partially paid', 'pending' => 'Pending cheque'][$filters['status'] ?? 'all'];
        $rowCount = $sections->sum(fn ($section) => $section['rows']->count());
    @endphp
    <div class="header">
        <div class="school">{{ $schoolName }}</div>
        @if($schoolAddress)<div class="muted">{{ $schoolAddress }}</div>@endif
        <h1>{{ $reportTitle }} - Student Payment List</h1>
        <div class="muted">Generated {{ now()->format('d M Y, h:i A') }} | {{ $class?->name ?? 'All classes' }}</div>
    </div>
    <div class="filters">
        <div><b>List:</b> {{ $statusLabel }} &nbsp; | &nbsp; <b>Installment year:</b> {{ ($filters['all_years'] ?? false) ? 'All years through '.today()->year : $year }}</div>
        <div>
            @if($type === 'exam')<b>Exam installments:</b> {{ count($selectedExamDates) }} selected &nbsp; | &nbsp;@endif
            <b>Facility fee:</b> {{ $includeFacility ? 'Included' : 'Excluded' }} &nbsp; | &nbsp;
            <b>Students:</b> {{ ($filters['include_inactive'] ?? false) ? 'Including inactive / alumni' : 'Active students' }} | {{ ($filters['include_former'] ?? false) ? 'Including former class students' : 'Current class members only' }}
        </div>
        @if(! empty($filters['q']))<div><b>Student search:</b> {{ $filters['q'] }}</div>@endif
    </div>
    <table class="summary"><tr>
        <td>Student rows<span class="value">{{ $rowCount }}</span></td>
        <td>Payments recorded for selected installments<span class="value">Rs {{ number_format($tracker['paid'], 2) }}</span></td>
        <td>Outstanding due now<span class="value unpaid">Rs {{ number_format($tracker['due'], 2) }}</span></td>
        <td>Students with unpaid installments<span class="value">{{ $tracker['unpaid_students'] }}</span></td>
    </tr></table>

    @forelse($sections as $section)
        @if($section['cycles']->isEmpty())
            <div class="empty">{{ $section['class']->name }}: No installments selected for this period.</div>
        @else
            @foreach($section['cycles']->chunk(4) as $chunk)
                <table class="fees">
                    <thead>
                        <tr>
                            <th style="width: 24%;">
                                <div class="class-name">{{ $section['class']->name }}</div>
                                Student / admission number
                                <div class="sub">{{ $section['rows']->count() }} students @if($section['cycles']->count() > 4) | Group {{ $loop->iteration }} of {{ $section['cycles']->chunk(4)->count() }} @endif</div>
                            </th>
                            @foreach($chunk as $cycle)
                                <th style="width: {{ 51 / $chunk->count() }}%;">
                                    {{ \App\Models\ClassFeeSchedule::TYPES[$cycle['schedule']->fee_type] }}<br>
                                    @if(isset($cycle['term']))Term {{ $cycle['term'] }}<br>@endif{{ $cycle['due']->format('d M Y') }}@if(isset($cycle['end']))<div class="sub">to {{ $cycle['end']->format('d M Y') }}</div>@endif
                                    <div class="sub">Rs {{ number_format($cycle['amount'], 2) }}@if($cycle['due']->gt(today())) - Upcoming @endif</div>
                                </th>
                            @endforeach
                            <th class="center" style="width: 12%;">Paid / due installments<br><span class="sub">All selected</span></th>
                            <th class="right" style="width: 13%;">Due now (Rs)<br><span class="sub">All selected</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($section['rows'] as $row)
                            <tr @class(['first-row' => $loop->first])>
                                <td><div class="name">{{ $row['student']->name }}</div><div class="sub">{{ $row['student']->admission_number ?: '-' }}@if((int) $row['student']->class_room_id !== (int) $section['class']->id) | Former class @endif</div></td>
                                @foreach($chunk as $key => $cycle)
                                    @php($installment = $row['installments'][$key])
                                    <td>
                                        <div class="status {{ $installment['due'] > 0 ? 'unpaid' : ($installment['status'] === 'Paid' ? 'paid' : 'muted') }}">{{ $installment['status'] }}</div>
                                        @if($installment['eligible'])
                                            <div class="sub">Paid Rs {{ number_format($installment['paid'], 2) }}</div>
                                            @if($installment['pending'] > 0)<div class="sub pending">Pending Rs {{ number_format($installment['pending'], 2) }}</div>@endif
                                            @if($installment['waived'] > 0)<div class="sub">Waived Rs {{ number_format($installment['waived'], 2) }}</div>@endif
                                        @endif
                                    </td>
                                @endforeach
                                <td class="center">{{ $row['paid_count'] }} / {{ $row['due_count'] }}@if($row['waived_count'] > 0)<div class="sub">{{ $row['waived_count'] }} settled with waiver</div>@endif</td>
                                <td class="right name">{{ number_format($row['due'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $chunk->count() + 3 }}" class="center muted">No students match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            @endforeach
            <div class="section-totals">{{ $section['class']->name }} totals: Payments recorded Rs {{ number_format($section['paid'], 2) }} | Due now Rs {{ number_format($section['due'], 2) }}</div>
        @endif
    @empty
        <div class="empty">No students or installments match the selected filters.</div>
    @endforelse
    <div class="note">Balances, counts, and the paid/unpaid filter use only the selected installments. Upcoming installments are not overdue. Pending cheques are not counted as paid. Historical payments settle fees but are excluded from revenue and ledger totals. Waivers settle balances without adding to collected income. Former class students are included only when the Include former class students filter is selected.</div>
</body>
</html>
