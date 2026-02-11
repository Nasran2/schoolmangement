<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>{{ $title ?? 'Cheque History' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 6px 0; }
        .meta { font-size: 12px; margin-bottom: 12px; color: #374151; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 5px; }
        th { background: #f3f4f6; text-align: left; }
        td.num { text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $title ?? 'Cheque History' }}</h1>
    <div class="meta">
        Range: {{ $filters['from'] ?? '—' }} to {{ $filters['to'] ?? '—' }}
        | Status: {{ $filters['status'] ?? 'all' }}
        | Type: {{ $filters['type'] ?? 'all' }}
        | Include pending: {{ !empty($filters['include_pending_cheques']) ? 'Yes' : 'No' }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 80px;">Date</th>
                <th style="width: 40px;">Dir</th>
                <th style="width: 70px;">Ref</th>
                <th style="width: 110px;">Party</th>
                <th>Description</th>
                <th style="width: 80px;">Cheque No</th>
                <th style="width: 90px;">Bank</th>
                <th style="width: 70px;">Status</th>
                <th style="width: 80px;">Cheque Date</th>
                <th style="width: 80px;">Passed Date</th>
                <th style="width: 70px;">In</th>
                <th style="width: 70px;">Out</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r['date']?->format('Y-m-d') }}</td>
                    <td>{{ $r['direction'] }}</td>
                    <td>{{ $r['ref'] }}</td>
                    <td>{{ $r['party'] }}</td>
                    <td>{{ $r['description'] }}</td>
                    <td>{{ $r['cheque_no'] }}</td>
                    <td>{{ $r['bank'] }}</td>
                    <td>{{ $r['status'] }}</td>
                    <td>{{ $r['cheque_date'] }}</td>
                    <td>{{ $r['passed_date'] }}</td>
                    <td class="num">{{ number_format((float)($r['in'] ?? 0), 2) }}</td>
                    <td class="num">{{ number_format((float)($r['out'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
