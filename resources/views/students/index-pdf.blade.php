<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Students Report</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 0; padding: 0; color: #1e293b; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
        .school-name { font-size: 20px; font-weight: bold; margin-bottom: 5px; color: #1e1b4b; }
        .school-contact { font-size: 11px; color: #475569; margin-bottom: 5px; }
        .report-title { font-size: 14px; font-weight: bold; margin-bottom: 8px; text-transform: uppercase; color: #4338ca; }
        .meta { font-size: 11px; color: #64748b; margin-bottom: 10px; line-height: 1.5; }
        .filter-badge { background-color: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-weight: bold; color: #334155; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: left; vertical-align: middle; }
        th { background-color: #f8fafc; font-weight: bold; color: #334155; font-size: 10px; text-transform: uppercase; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge-active { color: #15803d; font-weight: bold; }
        .badge-inactive { color: #b91c1c; font-weight: bold; }
        .badge-alumni { color: #b45309; font-weight: bold; }
        .total-row { background-color: #f8fafc; font-weight: bold; }
    </style>
</head>
<body>
    @php
        $schoolName = app('settings')->get('school.name', config('app.name', 'School'));
        $schoolAddress = app('settings')->get('school.address', '');
        $schoolPhone = app('settings')->get('school.phone', '');
        $schoolEmail = app('settings')->get('school.email', '');
    @endphp
    <div class="header">
        <div class="school-name">{{ $schoolName }}</div>
        @if(!empty($schoolAddress) || !empty($schoolPhone) || !empty($schoolEmail))
            <div class="school-contact">
                @if(!empty($schoolAddress))
                    <span>{{ $schoolAddress }}</span>
                @endif
                @if(!empty($schoolPhone))
                    @if(!empty($schoolAddress)) | @endif
                    <span>Phone: {{ $schoolPhone }}</span>
                @endif
                @if(!empty($schoolEmail))
                    @if(!empty($schoolAddress) || !empty($schoolPhone)) | @endif
                    <span>Email: {{ $schoolEmail }}</span>
                @endif
            </div>
        @endif
        <div class="report-title" style="margin-top: 10px;">Students Report</div>
        <div class="meta">
            <strong>Filters applied:</strong>
            @php
                $statusVal = $filters['status'] ?? 'all';
                $startVal = $filters['payment_start'] ?? 'all';
                $duesVal = $filters['payment_filter'] ?? 'all';
                $searchVal = $filters['q'] ?? '';
            @endphp
            Status: <span class="filter-badge">{{ ucfirst($statusVal) }}</span> | 
            Payment Start: <span class="filter-badge">{{ $startVal === 'set' ? 'Set' : ($startVal === 'not_set' ? 'Not Set' : 'All') }}</span> | 
            Dues: <span class="filter-badge">{{ $duesVal === 'never_paid' ? 'Never Paid' : ($duesVal === 'with_due' ? 'With Due' : ($duesVal === 'no_due' ? 'No Due' : 'All')) }}</span>
            @if(!empty($searchVal))
                | Search: <span class="filter-badge">"{{ $searchVal }}"</span>
            @endif
            <br>
            Generated: {{ now()->format('Y-m-d H:i A') }} | Total Records: {{ count($items) }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="4%">#</th>
                <th width="12%">Admission No</th>
                <th width="20%">Name</th>
                <th width="15%">Grade/Class</th>
                <th width="12%">Phone</th>
                <th width="18%">Address</th>
                <th width="8%" class="text-center">Status</th>
                <th width="11%" class="text-center">Payment Start</th>
                <th width="12%" class="text-right">Total Due</th>
            </tr>
        </thead>
        <tbody>
            @php $totalDuesSum = 0; @endphp
            @foreach($items as $index => $item)
            @php 
                $due = (float) ($item->computed_due_amount ?? $item->due_amount ?? 0); 
                $totalDuesSum += $due;
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->admission_number ?? $item->id }}</td>
                <td style="font-weight: bold;">{{ $item->name }}</td>
                <td>{{ $item->classRoom?->name ?? ($item->class ?? '-') }}</td>
                <td>{{ $item->phone ?? '-' }}</td>
                <td>{{ $item->address ?? '-' }}</td>
                <td class="text-center">
                    @if($item->alumni)
                        <span class="badge-alumni">Alumni</span>
                    @elseif($item->active)
                        <span class="badge-active">Active</span>
                    @else
                        <span class="badge-inactive">Inactive</span>
                    @endif
                </td>
                <td class="text-center">{{ optional($item->fee_start_date)->format('d-m-Y') ?? 'Not set' }}</td>
                <td class="text-right" style="font-weight: bold; color: {{ $due > 0 ? '#b91c1c' : '#1e293b' }};">
                    Rs {{ number_format($due, 2) }}
                </td>
            </tr>
            @endforeach
            
            <tr class="total-row">
                <td colspan="8" class="text-right" style="padding: 10px;">TOTAL DUE (RECEIVABLE)</td>
                <td class="text-right" style="color: #b91c1c; font-size: 12px;">Rs {{ number_format($totalDuesSum, 2) }}</td>
            </tr>
            
            @if($items->isEmpty())
            <tr>
                <td colspan="9" class="text-center" style="padding: 20px; color: #64748b;">No students matched the active filters.</td>
            </tr>
            @endif
        </tbody>
    </table>
</body>
</html>
