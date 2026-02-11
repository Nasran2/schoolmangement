<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>EPF/ETF Totals</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 6px 0; }
        .muted { color: #6b7280; font-size: 11px; }
        .grid { width: 100%; margin-top: 10px; }
        .grid td { padding: 6px 8px; border: 1px solid #e5e7eb; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; }
        th { background: #f9fafb; text-align: left; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h1>EPF/ETF Totals</h1>
    <div class="muted">Generated: {{ now()->format('d-m-Y H:i') }}</div>

    <table class="grid">
        <tr>
            <td><strong>Teacher EPF</strong><div>Rs {{ number_format((float)($totals['employee_epf'] ?? 0), 2) }}</div></td>
            <td><strong>Company EPF</strong><div>Rs {{ number_format((float)($totals['employer_epf'] ?? 0), 2) }}</div></td>
            <td><strong>Company ETF</strong><div>Rs {{ number_format((float)($totals['employer_etf'] ?? 0), 2) }}</div></td>
            <td><strong>Grand Total</strong><div>Rs {{ number_format((float)($totals['grand_total'] ?? 0), 2) }}</div></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Teacher</th>
                <th class="right">Payments</th>
                <th class="right">Teacher EPF</th>
                <th class="right">Company EPF</th>
                <th class="right">Company ETF</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($teacherTotals ?? [] as $row)
                <tr>
                    <td>{{ $row['teacher']?->name }}</td>
                    <td class="right">{{ (int)($row['payments'] ?? 0) }}</td>
                    <td class="right">{{ number_format((float)($row['employee_epf'] ?? 0), 2) }}</td>
                    <td class="right">{{ number_format((float)($row['employer_epf'] ?? 0), 2) }}</td>
                    <td class="right">{{ number_format((float)($row['employer_etf'] ?? 0), 2) }}</td>
                    <td class="right">{{ number_format((float)($row['total'] ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted">No records for selected filters.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5" class="right">Grand Total</th>
                <th class="right">{{ number_format((float)($totals['grand_total'] ?? 0), 2) }}</th>
            </tr>
        </tfoot>
    </table>

    @if(($groupByMonth ?? false) && !empty($monthTotals ?? []))
        <table>
            <thead>
                <tr>
                    <th>Month</th>
                    <th class="right">Payments</th>
                    <th class="right">Teacher EPF</th>
                    <th class="right">Company EPF</th>
                    <th class="right">Company ETF</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($monthTotals as $m)
                    @php
                        $total = (float)($m['employee_epf'] ?? 0) + (float)($m['employer_epf'] ?? 0) + (float)($m['employer_etf'] ?? 0);
                    @endphp
                    <tr>
                        <td>{{ $m['label'] ?? ($m['month'] ?? '') }}</td>
                        <td class="right">{{ (int)($m['payments'] ?? 0) }}</td>
                        <td class="right">{{ number_format((float)($m['employee_epf'] ?? 0), 2) }}</td>
                        <td class="right">{{ number_format((float)($m['employer_epf'] ?? 0), 2) }}</td>
                        <td class="right">{{ number_format((float)($m['employer_etf'] ?? 0), 2) }}</td>
                        <td class="right">{{ number_format($total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
