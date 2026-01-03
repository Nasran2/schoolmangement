<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Collected vs Expected</title>
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
    <h1>Collected vs Expected (Monthly Fees)</h1>
    <div class="muted">Generated: {{ now()->format('Y-m-d H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th class="right">Expected</th>
                <th class="right">Collected</th>
                <th class="right">Due</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r['label'] ?? $r['month'] }}</td>
                    <td class="right">{{ number_format((float) $r['expected'], 2) }}</td>
                    <td class="right">{{ number_format((float) $r['collected'], 2) }}</td>
                    <td class="right">{{ number_format((float) $r['due'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th class="right">Totals</th>
                <th class="right">{{ number_format((float) ($totals['expected'] ?? 0), 2) }}</th>
                <th class="right">{{ number_format((float) ($totals['collected'] ?? 0), 2) }}</th>
                <th class="right">{{ number_format((float) ($totals['due'] ?? 0), 2) }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
