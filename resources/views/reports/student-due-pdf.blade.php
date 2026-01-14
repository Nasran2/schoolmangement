<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Due Report</title>
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
    <h1>Student Due Amount Report</h1>
    <div class="muted">Generated: {{ now()->format('d-m-Y H:i') }}</div>

    <table>
        <thead>
            <tr>
                <th>Admission No</th>
                <th>Student</th>
                <th>Class</th>
                <th class="right">Monthly Fee</th>
                <th class="right">Months Due</th>
                <th class="right">Paid</th>
                <th class="right">Due</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                @php($s = $row['student'])
                <tr>
                    <td>{{ $s->admission_number }}</td>
                    <td>{{ $s->name }}</td>
                    <td>{{ $row['class_room']?->name }}</td>
                    <td class="right">{{ number_format((float)$row['monthly_fee'], 2) }}</td>
                    <td class="right">{{ (int)$row['months_due'] }}</td>
                    <td class="right">{{ number_format((float)$row['paid'], 2) }}</td>
                    <td class="right">{{ number_format((float)$row['due'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6" class="right">Total Due</th>
                <th class="right">{{ number_format((float)$totalDue, 2) }}</th>
            </tr>
        </tfoot>
    </table>
</body>
</html>
