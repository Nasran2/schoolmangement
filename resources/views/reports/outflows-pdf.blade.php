<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Outflows</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        .header { text-align:center; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 15px; }
        .header h1 { margin: 0; font-size: 20px; }
        .muted { color: #6b7280; }
        .filters { background: #f3f4f6; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 12px; }
        table { width:100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; vertical-align: top; }
        th { background: #f9fafb; text-align: left; }
        td.amount { text-align: right; font-weight: 700; color: #b91c1c; }
        .total { margin-top: 10px; text-align: right; font-size: 14px; font-weight: 700; }
    </style>
</head>
<body>
    <div class="header">
        <h1>All Outflows</h1>
        <div class="muted">Generated on {{ now()->format('M d, Y - H:i A') }}</div>
    </div>

    <div class="filters">
        <div><strong>From:</strong> {{ $filters['from'] ?? '' }} &nbsp; <strong>To:</strong> {{ $filters['to'] ?? '' }}</div>
        <div class="muted"><strong>Method:</strong> {{ $filters['method'] ?? 'all' }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 72px;">Date</th>
                <th style="width: 110px;">Type</th>
                <th style="width: 120px;">Category</th>
                <th style="width: 140px;">Party</th>
                <th style="width: 90px;">Method</th>
                <th style="width: 90px; text-align:right;">Amount</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr>
                    <td>{{ $r['date'] ?? '' }}</td>
                    <td>{{ $r['type'] ?? '' }}</td>
                    <td>{{ $r['category'] ?? '' }}</td>
                    <td>{{ $r['party'] ?? '' }}</td>
                    <td>{{ $r['method'] ?? '' }}</td>
                    <td class="amount">{{ number_format((float)($r['amount'] ?? 0), 2) }}</td>
                    <td>{{ $r['notes'] ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted" style="text-align:center; padding: 18px;">No outflow records found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="total">Total Outflow: Rs {{ number_format((float)($totalAmount ?? 0), 2) }}</div>
</body>
</html>
