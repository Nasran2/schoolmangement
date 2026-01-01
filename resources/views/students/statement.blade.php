<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Student Statement</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111; }
        .header { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
        .title { font-size:18px; font-weight:700; }
        .card { border:1px solid #e5e7eb; border-radius:8px; padding:12px; margin-bottom:16px; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:8px 10px; border-bottom:1px solid #e5e7eb; font-size:12px; }
        th { text-align:left; background:#f8fafc; }
        .right { text-align:right; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Student Statement</div>
        <div style="font-size:12px;">Generated: {{ now()->format('Y-m-d H:i') }}</div>
    </div>

    <div class="card">
        <div style="font-size:14px; font-weight:600;">{{ $student->name }}</div>
        <div style="font-size:12px;">Admission No: {{ $student->admission_number ?? '-' }}</div>
        <div style="font-size:12px;">Class: {{ $student->classRoom?->name ?? ($student->class ?? '-') }}</div>
        <div style="font-size:12px;">Phone: {{ $student->phone ?? '-' }}</div>
        <div style="font-size:12px;">Guardian: {{ $student->guardian_name ?? '-' }} {{ $student->guardian_phone ? '(' . $student->guardian_phone . ')' : '' }}</div>
    </div>

    <div class="card">
        <div style="font-size:13px; font-weight:600; margin-bottom:6px;">Account Summary</div>
        <div style="display:flex; gap:16px; font-size:12px;">
            <div>Expected: <strong>{{ number_format($summary['expectedDue'] ?? 0, 2) }}</strong></div>
            <div>Paid: <strong>{{ number_format($summary['paidMonthlyFee'] ?? 0, 2) }}</strong></div>
            <div>Balance: <strong>{{ number_format($summary['netDue'] ?? 0, 2) }}</strong></div>
        </div>
    </div>

    <div class="card">
        <div style="font-size:13px; font-weight:600; margin-bottom:6px;">Payments</div>
        <table>
            <thead>
                <tr>
                    <th>Bill</th>
                    <th>Date</th>
                    <th>Category</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $p)
                    <tr>
                        <td>{{ $p->bill_no ?? '-' }}</td>
                        <td>{{ optional($p->paid_at)->format('Y-m-d') }}</td>
                        <td>{{ $p->category?->name }}</td>
                        <td class="right">{{ number_format($p->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No payments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(!empty($cycles))
    <div class="card">
        <div style="font-size:13px; font-weight:600; margin-bottom:6px;">Billing Cycles</div>
        <table>
            <thead>
                <tr>
                    <th>Start</th>
                    <th>End</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cycles as $cy)
                    <tr>
                        <td>{{ $cy['start'] }}</td>
                        <td>{{ $cy['end'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</body>
</html>
