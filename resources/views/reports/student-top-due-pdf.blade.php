<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Top Due Students</title>
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
    <h1>Top Due Students</h1>
    <div class="muted">Generated: {{ now()->format('Y-m-d H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Class</th>
                <th>Phone</th>
                <th>WhatsApp</th>
                <th class="right">Due</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r['student']->name }}</td>
                    <td>{{ $r['class_room']?->name }}</td>
                    <td>{{ $r['student']->phone }}</td>
                    <td>{{ $r['student']->whatsapp_number }}</td>
                    <td class="right">{{ number_format((float) $r['due'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th class="right" colspan="4">Total Due (Top List)</th>
                <th class="right">{{ number_format((float) $totalDue, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
