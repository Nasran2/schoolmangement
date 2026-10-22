<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>{{ $title ?? 'Transactions' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h1 { font-size: 17px; margin: 0 0 4px 0; color: #111827; }
        .meta { font-size: 10px; margin-bottom: 12px; color: #4b5563; }
        
        .summary-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 14px;
        }
        .summary-table { width: 100%; border: none; }
        .summary-table td { border: none; padding: 3px 6px; font-size: 11px; }
        .summary-val { font-weight: bold; font-family: monospace; }

        table.data-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data-table th, table.data-table td { border: 1px solid #e5e7eb; padding: 5px 6px; }
        table.data-table th { background: #f3f4f6; text-align: left; font-size: 10px; font-weight: bold; color: #374151; }
        td.num { text-align: right; font-family: monospace; }
        
        .totals { margin-top: 10px; font-size: 11px; text-align: right; }
    </style>
</head>
<body>
    <h1>{{ $title ?? 'Transactions' }}</h1>
    <div class="meta">
        <strong>Account:</strong> {{ strtoupper($account ?? '—') }}
        @if(!empty($filters['from']) || !empty($filters['to']))
            | <strong>Period:</strong> {{ $filters['from'] ?? 'Start' }} to {{ $filters['to'] ?? 'End' }}
        @endif
        | <strong>Generated:</strong> {{ now()->toDateTimeString() }}
    </div>

    @if(($account ?? '') === 'cash')
        <div class="summary-box">
            <table class="summary-table">
                <tr>
                    <td><strong>Cash in Hand (Today):</strong> <span class="summary-val" style="color: #047857;">Rs {{ number_format((float)($liveCashToday ?? 0), 2) }}</span></td>
                    <td><strong>Period Opening:</strong> <span class="summary-val">Rs {{ number_format((float)($openingBalance ?? 0), 2) }}</span></td>
                    <td><strong>Total In:</strong> <span class="summary-val" style="color: #047857;">+Rs {{ number_format((float)($totalIn ?? 0), 2) }}</span></td>
                    <td><strong>Total Out:</strong> <span class="summary-val" style="color: #b91c1c;">-Rs {{ number_format((float)($totalOut ?? 0), 2) }}</span></td>
                    <td><strong>Period Closing:</strong> <span class="summary-val">Rs {{ number_format((float)($closingBalance ?? 0), 2) }}</span></td>
                </tr>
            </table>
        </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 75px;">Date</th>
                <th style="width: 70px;">Type</th>
                <th style="width: 85px;">Ref</th>
                <th>Description</th>
                <th style="width: 75px; text-align: right;">In (Rs)</th>
                <th style="width: 75px; text-align: right;">Out (Rs)</th>
                @if(($account ?? '') === 'cash')
                    <th style="width: 85px; text-align: right;">Cash Balance (Rs)</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r['date']?->format('Y-m-d') }}</td>
                    <td>{{ $r['type'] }}</td>
                    <td style="font-family: monospace;">{{ $r['ref'] }}</td>
                    <td>{{ $r['description'] }}</td>
                    <td class="num">{{ !empty($r['in']) && (float)$r['in'] > 0 ? number_format((float)$r['in'], 2) : '—' }}</td>
                    <td class="num">{{ !empty($r['out']) && (float)$r['out'] > 0 ? number_format((float)$r['out'], 2) : '—' }}</td>
                    @if(($account ?? '') === 'cash')
                        <td class="num" style="font-weight: bold;">{{ number_format((float)($r['balance'] ?? 0), 2) }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <strong>Total In:</strong> Rs {{ number_format((float)($totalIn ?? 0), 2) }}
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Total Out:</strong> Rs {{ number_format((float)($totalOut ?? 0), 2) }}
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Net Change:</strong> Rs {{ number_format((float)($totalIn - $totalOut), 2) }}
        @if(($account ?? '') === 'cash')
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <strong>Closing Cash in Hand:</strong> Rs {{ number_format((float)($closingBalance ?? 0), 2) }}
        @endif
    </div>
</body>
</html>
