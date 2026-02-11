<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Seminars Collection</title>
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
    <h1>Seminars Collection</h1>
    <div class="meta">
        @if(!empty($filters['from']) || !empty($filters['to']))
            Range: {{ $filters['from'] ?? '—' }} to {{ $filters['to'] ?? '—' }}
        @else
            Range: —
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 80px;">Date</th>
                <th>Seminar</th>
                <th style="width: 55px;">Students</th>
                <th style="width: 45px;">Paid</th>
                <th style="width: 75px;">Expected</th>
                <th style="width: 75px;">Collected</th>
                <th style="width: 75px;">Due</th>
                <th style="width: 80px;">Teacher Pay</th>
                <th style="width: 70px;">Teacher Paid</th>
                <th style="width: 70px;">Teacher Due</th>
                <th style="width: 75px;">Net Margin</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r->date }}</td>
                    <td>{{ $r->seminar_name }}</td>
                    <td class="num">{{ (int)$r->total }}</td>
                    <td class="num">{{ (int)$r->paid_count }}</td>
                    <td class="num">{{ number_format((float)$r->expected, 2) }}</td>
                    <td class="num">{{ number_format((float)$r->collected, 2) }}</td>
                    <td class="num">{{ number_format((float)$r->due_amount, 2) }}</td>
                    <td class="num">{{ number_format((float)$r->teacher_payment, 2) }}</td>
                    <td class="num">{{ number_format((float)$r->teacher_paid, 2) }}</td>
                    <td class="num">{{ number_format((float)$r->teacher_due, 2) }}</td>
                    <td class="num">{{ number_format((float)$r->net_margin, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
