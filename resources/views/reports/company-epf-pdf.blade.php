<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Company EPF Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 6px 0; }
        .muted { color: #6b7280; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; }
        th { background: #f9fafb; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>Company EPF Report</h1>
    <div class="muted">Generated: {{ now()->format('d-m-Y H:i') }}</div>

    @if(($groupByMonth ?? false) && !empty($monthTotals ?? []))
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="right">Payments</th>
                    <th class="right">Total EPF</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthTotals as $m)
                    <tr>
                        <td>{{ $m['label'] ?? ($m['month'] ?? '') }}</td>
                        <td class="right">{{ (int) ($m['payments'] ?? 0) }}</td>
                        <td class="right">{{ number_format((float) ($m['total'] ?? 0), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table>
        <thead>
            <tr>
                <th>Receipt</th>
                <th>Paid At</th>
                <th>Month</th>
                <th>Teacher</th>
                <th class="right">Basic Salary</th>
                <th class="right">EPF</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $row)
                @php
                    $epf = $row->employer_epf_amount ?? 0;
                @endphp
                <tr>
                    <td>{{ $row->receipt_number }}</td>
                    <td>{{ optional($row->paid_at)->format('d-m-Y') }}</td>
                    <td>{{ $row->payment_month ? \Carbon\Carbon::parse($row->payment_month . '-01')->format('M Y') : 'N/A' }}</td>
                    <td>{{ $row->teacher?->name }}</td>
                    <td class="right">{{ number_format((float)$row->base_salary, 2) }}</td>
                    <td class="right">{{ number_format((float)$epf, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="right">Total EPF</th>
                <th class="right">{{ number_format((float)$totalAmount, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
