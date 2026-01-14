<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fee Collection by Class</title>
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
    <h1>Fee Collection by Class</h1>
    <div class="muted">Generated: {{ now()->format('d-m-Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>Class</th>
                <th class="right">Payments</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r->class_name }}</td>
                    <td class="right">{{ (int) $r->payments }}</td>
                    <td class="right">{{ number_format((float) $r->total_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th class="right" colspan="2">Total Collected</th>
                <th class="right">{{ number_format((float) $totalAmount, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
