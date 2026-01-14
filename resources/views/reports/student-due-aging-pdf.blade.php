<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Due Aging</title>
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
    <h1>Student Due Aging</h1>
    <div class="muted">Generated: {{ now()->format('d-m-Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>Bucket</th>
                <th class="right">Students</th>
                <th class="right">Total Due</th>
            </tr>
        </thead>
        <tbody>
            @foreach($buckets as $b)
                <tr>
                    <td>{{ $b['label'] }}</td>
                    <td class="right">{{ (int) $b['students'] }}</td>
                    <td class="right">{{ number_format((float) $b['due'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Class</th>
                <th>Bucket</th>
                <th class="right">Unpaid Months</th>
                <th class="right">Due</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r['student']->name }}</td>
                    <td>{{ $r['class_room']?->name }}</td>
                    <td>{{ $r['bucket'] }}</td>
                    <td class="right">{{ (int) $r['unpaid_months'] }}</td>
                    <td class="right">{{ number_format((float) $r['due'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
