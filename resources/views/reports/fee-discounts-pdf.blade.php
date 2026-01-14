<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Waiver Report</title>
    <style>
        body { font-family: ui-sans-serif, system-ui; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <h2>Discount / Waiver Report</h2>
    <p>Total Waivers: <strong>Rs {{ number_format((float) $totalAmount, 2) }}</strong></p>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Bill No</th>
                <th>Student</th>
                <th>Admission</th>
                <th>Class</th>
                <th>Category</th>
                <th class="right">Amount</th>
                <th>Reason</th>
                <th>By</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $a)
                <tr>
                    <td>{{ optional($a->created_at)->format('d-m-Y') }}</td>
                    <td>{{ $a->revenue?->bill_no }}</td>
                    <td>{{ $a->student?->name }}</td>
                    <td>{{ $a->student?->admission_number }}</td>
                    <td>{{ $a->student?->classRoom?->name }}</td>
                    <td>{{ $a->revenue?->category?->name }}</td>
                    <td class="right">{{ number_format((float) $a->amount, 2) }}</td>
                    <td>{{ $a->reason }}</td>
                    <td>{{ $a->creator?->name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
