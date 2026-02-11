<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>{{ $title ?? 'Transactions' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 6px 0; }
        .meta { font-size: 12px; margin-bottom: 12px; color: #374151; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px; }
        th { background: #f3f4f6; text-align: left; }
        td.num { text-align: right; }
        .totals { margin-top: 10px; }
    </style>
</head>
<body>
    <h1>{{ $title ?? 'Transactions' }}</h1>
    <div class="meta">
        Account: {{ strtoupper($account ?? '—') }}
        @if(!empty($filters['from']) || !empty($filters['to']))
            | Range: {{ $filters['from'] ?? '—' }} to {{ $filters['to'] ?? '—' }}
        @endif
        @if(!empty($filters['include_pending_cheques']))
            | Include pending cheques: Yes
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 90px;">Date</th>
                <th style="width: 80px;">Type</th>
                <th style="width: 90px;">Ref</th>
                <th>Description</th>
                <th style="width: 110px;">Method</th>
                <th style="width: 80px;">In</th>
                <th style="width: 80px;">Out</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r['date']?->format('Y-m-d') }}</td>
                    <td>{{ $r['type'] }}</td>
                    <td>{{ $r['ref'] }}</td>
                    <td>{{ $r['description'] }}</td>
                    <td>{{ $r['method'] }}</td>
                    <td class="num">{{ number_format((float)($r['in'] ?? 0), 2) }}</td>
                    <td class="num">{{ number_format((float)($r['out'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <strong>Total In:</strong> {{ number_format((float)($totalIn ?? 0), 2) }}
        &nbsp;&nbsp;|
        <strong>Total Out:</strong> {{ number_format((float)($totalOut ?? 0), 2) }}
    </div>
</body>
</html>
