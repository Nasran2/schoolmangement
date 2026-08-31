<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Salary Report - {{ $teacher->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        h1,
        h2,
        h3 {
            color: #1a202c;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .teacher-info {
            margin-bottom: 30px;
        }

        .teacher-info table {
            width: 100%;
        }

        .teacher-info td {
            padding: 5px 0;
        }

        .teacher-info strong {
            display: inline-block;
            width: 120px;
        }

        .section-title {
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 16px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #cbd5e0;
            padding: 8px;
            text-align: left;
        }

        table.data-table th {
            background-color: #f7fafc;
            font-weight: bold;
        }

        .text-right {
            text-align: right !important;
        }

        .status-paid {
            color: #38a169;
            font-weight: bold;
        }

        .status-unpaid {
            color: #e53e3e;
            font-weight: bold;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #718096;
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>Teacher Salary Report</h1>
        <p>Generated on {{ now()->format('M d, Y h:i A') }}</p>
    </div>

    <div class="teacher-info">
        <table>
            <tr>
                <td><strong>Name:</strong> {{ $teacher->name }}</td>
                <td><strong>Email:</strong> {{ $teacher->email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Phone:</strong> {{ $teacher->phone ?? 'N/A' }}</td>
                <td><strong>Joining Date:</strong> {{ optional($teacher->joining_date)->format('M d, Y') ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Status:</strong> {{ $teacher->active ? 'Active' : 'Inactive' }}</td>
                <td><strong>Base Salary:</strong> Rs {{ number_format($teacher->salary_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <h2 class="section-title">Pending & Paid Status (Last 12 Months)</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Month</th>
                <th>Status</th>
                <th class="text-right">Amount Paid</th>
            </tr>
        </thead>
        <tbody>
            @foreach(array_reverse($monthlyPayments) as $mp)
            <tr>
                <td>{{ $mp['label'] }}</td>
                <td>
                    @if($mp['paid'])
                    <span class="status-paid">Paid</span>
                    @else
                    <span class="status-unpaid">Pending</span>
                    @endif
                </td>
                <td class="text-right">
                    @if($mp['paid'])
                    Rs {{ number_format($mp['amount'], 2) }}
                    @else
                    -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h2 class="section-title">Salary Payment History</h2>
    @if($payments->count() > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th>Receipt</th>
                <th>Month</th>
                <th>Date Paid</th>
                <th class="text-right">Base Salary</th>
                <th class="text-right">Deductions</th>
                <th class="text-right">Net Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                <td>{{ $payment->receipt_number }}</td>
                <td>
                    {{ $payment->payment_month ? \Carbon\Carbon::parse($payment->payment_month . '-01')->format('M Y') : 'N/A' }}
                </td>
                <td>{{ optional($payment->paid_at)->format('M d, Y') }}</td>
                <td class="text-right">Rs {{ number_format($payment->base_salary ?? $payment->amount, 2) }}</td>
                <td class="text-right">
                    @if($payment->total_deductions > 0)
                    Rs {{ number_format($payment->total_deductions, 2) }}
                    @else
                    -
                    @endif
                </td>
                <td class="text-right">Rs {{ number_format($payment->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p>No payment history available.</p>
    @endif

    <div class="footer">
        This is a computer generated report and does not require a signature.
    </div>

</body>

</html>