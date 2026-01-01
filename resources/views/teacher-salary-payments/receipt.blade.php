<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Salary Payment Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 30px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #1e3a8a;
            font-size: 28px;
        }
        .header p {
            margin: 5px 0 0 0;
            color: #666;
        }
        .receipt-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            padding: 15px;
            background: #f3f4f6;
            border-radius: 8px;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #374151;
            font-size: 12px;
            text-transform: uppercase;
        }
        .info-value {
            color: #111827;
            font-size: 14px;
            margin-top: 3px;
        }
        .teacher-info {
            grid-column: 1 / -1;
            border-top: 1px solid #d1d5db;
            padding-top: 15px;
            margin-top: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th {
            background: #e5e7eb;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #d1d5db;
        }
        table td {
            padding: 12px;
            border: 1px solid #d1d5db;
        }
        table tr:nth-child(even) {
            background: #f9fafb;
        }
        .deductions-table {
            margin: 20px 0;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            border-radius: 4px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
        }
        .summary-row.total {
            border-top: 2px solid #3b82f6;
            padding-top: 12px;
            font-weight: bold;
            font-size: 16px;
            color: #1e3a8a;
        }
        .summary-label {
            color: #374151;
        }
        .summary-value {
            color: #111827;
            font-weight: 600;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        .green {
            color: #059669;
        }
        .red {
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>💳 Salary Payment Receipt</h1>
            <p>Official Payment Confirmation</p>
        </div>

        <!-- Receipt Information -->
        <div class="receipt-info">
            <div class="info-item">
                <div class="info-label">Payment Date</div>
                <div class="info-value">{{ $payment->paid_at->format('d M Y') }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Payment Month</div>
                <div class="info-value">{{ $payment->payment_month }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Receipt Number</div>
                <div class="info-value">#{{ $payment->id }}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value">
                    <span style="display: inline-block; padding: 4px 8px; background: #d1fae5; color: #065f46; border-radius: 4px; font-size: 12px; font-weight: bold;">
                        COMPLETED
                    </span>
                </div>
            </div>

            <!-- Teacher Information -->
            <div class="teacher-info">
                <div class="info-item">
                    <div class="info-label">Teacher Name</div>
                    <div class="info-value">{{ $payment->teacher->name }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div class="info-value">{{ $payment->teacher->phone ?? 'N/A' }}</div>
                </div>
            </div>
        </div>

        <!-- Payment Details Table -->
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Base Salary</td>
                    <td style="text-align: right; font-weight: bold;">Rs {{ number_format($payment->base_salary, 2) }}</td>
                </tr>
                @if($payment->deductions && count($payment->deductions) > 0)
                    @foreach($payment->deductions as $deduction)
                    <tr>
                        <td style="padding-left: 30px;">- {{ $deduction['reason'] }}</td>
                        <td style="text-align: right; color: #dc2626;">Rs {{ number_format($deduction['amount'], 2) }}</td>
                    </tr>
                    @endforeach
                    <tr style="background: #fef2f2;">
                        <td style="font-weight: bold;">Total Deductions</td>
                        <td style="text-align: right; font-weight: bold; color: #dc2626;">Rs {{ number_format($payment->total_deductions, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- Summary Section -->
        <div class="summary">
            <div class="summary-row">
                <span class="summary-label">Base Salary:</span>
                <span class="summary-value">Rs {{ number_format($payment->base_salary, 2) }}</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Total Deductions:</span>
                <span class="summary-value red">-Rs {{ number_format($payment->total_deductions, 2) }}</span>
            </div>
            <div class="summary-row total">
                <span class="summary-label">Net Amount Paid:</span>
                <span class="summary-value green">Rs {{ number_format($payment->amount, 2) }}</span>
            </div>
        </div>

        <!-- Notes -->
        @if($payment->notes)
        <div style="margin-top: 20px; padding: 15px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
            <strong style="color: #92400e;">Notes:</strong>
            <p style="margin: 5px 0 0 0; color: #78350f;">{{ $payment->notes }}</p>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>This is an official salary payment receipt. Please retain for your records.</p>
            <p>Generated on {{ now()->format('d M Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
