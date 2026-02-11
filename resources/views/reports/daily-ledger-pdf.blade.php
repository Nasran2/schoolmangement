<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Ledger</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Georgia, 'Times New Roman', serif; color: #222; background: #fff; line-height: 1.4; }
        .container { padding: 18px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #1f2937; padding-bottom: 10px; margin-bottom: 14px; }
        .title { font-size: 16px; font-weight: 800; letter-spacing: .02em; text-transform: uppercase; }
        .sub { font-size: 11px; color: #6b7280; margin-top: 4px; }
        .meta { text-align: right; font-size: 11px; color: #374151; }
        .filters { border: 1px solid #e5e7eb; background: #f9fafb; padding: 10px; border-radius: 6px; margin-bottom: 12px; font-size: 11px; }
        .row { display: table; width: 100%; table-layout: fixed; }
        .cell { display: table-cell; width: 50%; vertical-align: top; padding-right: 10px; }
        .k { color: #111827; font-weight: 700; }
        .v { color: #374151; }

        h3 { font-size: 12px; margin: 12px 0 6px; color: #111827; }
        table { width: 100%; border-collapse: collapse; }
        th, td { font-size: 10.5px; padding: 6px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        th { color: #6b7280; text-align: left; background: #f3f4f6; }
        .num { text-align: right; }
        .summary { margin-top: 10px; border-top: 1px dotted #9ca3af; padding-top: 8px; font-size: 11px; }
        .summary .rowline { display: table; width: 100%; }
        .summary .rowline div { display: table-cell; width: 50%; }
        .closing { font-weight: 800; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div>
            <div class="title">{{ strtoupper($school['name'] ?? 'SCHOOL') }}</div>
            <div class="sub">Daily Ledger</div>
        </div>
        <div class="meta">
            <div><span class="k">Period:</span> <span class="v">{{ $filters['from'] ?? '' }} — {{ $filters['to'] ?? '' }}</span></div>
            <div><span class="k">Method:</span> <span class="v">{{ $filters['method'] ?? 'all' }}</span></div>
            <div><span class="k">Include Pending Cheques:</span> <span class="v">{{ !empty($filters['include_pending_cheques']) ? 'Yes' : 'No' }}</span></div>
            <div class="muted">Generated: {{ now()->format('Y-m-d H:i') }}</div>
        </div>
    </div>

    <div class="filters">
        <div class="row">
            <div class="cell">
                <div><span class="k">Opening Balance As of:</span> <span class="v">{{ $bbfDate->toDateString() }}</span></div>
            </div>
            <div class="cell">
                <div class="num"><span class="k">Opening:</span> <span class="v">Rs {{ number_format((float) $openingBalance, 2) }}</span></div>
            </div>
        </div>

        <div class="row" style="margin-top: 6px;">
            <div class="cell">
                <div><span class="k">Opening Cash:</span> <span class="v">Rs {{ number_format((float) ($openingBalanceCash ?? 0), 2) }}</span></div>
            </div>
            <div class="cell">
                <div class="num"><span class="k">Opening Bank:</span> <span class="v">Rs {{ number_format((float) ($openingBalanceBank ?? 0), 2) }}</span></div>
            </div>
        </div>
    </div>

    <h3>Revenues</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 10%">Date</th>
                <th style="width: 10%">Bill</th>
                <th style="width: 18%">Student</th>
                <th style="width: 16%">Category</th>
                <th>Details</th>
                <th style="width: 12%">Method</th>
                <th style="width: 10%">Cheque Date</th>
                <th class="num" style="width: 12%">Amount</th>
            </tr>
        </thead>
        <tbody>
        @forelse($revenues as $r)
            <tr>
                <td>{{ $r['date'] ? $r['date']->format('Y-m-d') : '—' }}</td>
                <td>{{ $r['ref'] }}</td>
                <td>{{ $r['student'] }}</td>
                <td>{{ $r['category'] }}</td>
                <td>{{ $r['description'] }}</td>
                <td>{{ $r['method'] }}</td>
                <td>{{ $r['cheque_date'] ?? '—' }}</td>
                <td class="num">{{ number_format((float) $r['in'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="muted">No revenue records found.</td></tr>
        @endforelse
            <tr>
                <td colspan="7" class="k">Total Revenue</td>
                <td class="num k">{{ number_format((float) $totalIn, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h3>Expenses (including refunds)</h3>
    <table>
        <thead>
            <tr>
                <th style="width: 10%">Date</th>
                <th style="width: 10%">Type</th>
                <th style="width: 10%">Ref</th>
                <th style="width: 18%">Student</th>
                <th style="width: 16%">Category</th>
                <th>Details</th>
                <th style="width: 12%">Method</th>
                <th style="width: 10%">Cheque Date</th>
                <th class="num" style="width: 12%">Amount</th>
            </tr>
        </thead>
        <tbody>
        @forelse($expenses as $e)
            <tr>
                <td>{{ $e['date'] ? $e['date']->format('Y-m-d') : '—' }}</td>
                <td>{{ $e['type'] }}</td>
                <td>{{ $e['ref'] }}</td>
                <td>{{ $e['student'] }}</td>
                <td>{{ $e['category'] }}</td>
                <td>{{ $e['description'] }}</td>
                <td>{{ $e['method'] }}</td>
                <td>{{ $e['cheque_date'] ?? '—' }}</td>
                <td class="num">{{ number_format((float) $e['out'], 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="9" class="muted">No expense records found.</td></tr>
        @endforelse
            <tr>
                <td colspan="8" class="k">Total Expense</td>
                <td class="num k">{{ number_format((float) $totalOut, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="summary">
        <div class="rowline">
            <div><span class="k">Opening:</span> Rs {{ number_format((float) $openingBalance, 2) }}</div>
            <div class="num"><span class="k">Revenue:</span> Rs {{ number_format((float) $totalIn, 2) }}</div>
        </div>
        <div class="rowline">
            <div><span class="k">Expense:</span> Rs {{ number_format((float) $totalOut, 2) }}</div>
            <div class="num closing"><span class="k">Closing:</span> Rs {{ number_format((float) $closingBalance, 2) }}</div>
        </div>

        <div class="rowline" style="margin-top: 6px;">
            <div><span class="k">Closing Cash:</span> Rs {{ number_format((float) ($closingBalanceCash ?? 0), 2) }}</div>
            <div class="num"><span class="k">Closing Bank:</span> Rs {{ number_format((float) ($closingBalanceBank ?? 0), 2) }}</div>
        </div>
    </div>
</div>
</body>
</html>
