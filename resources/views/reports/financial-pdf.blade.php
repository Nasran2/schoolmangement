<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background-color: #f9fafb;
            line-height: 1.6;
        }

        .container {
            max-width: 100%;
            margin: 0;
            padding: 20px;
            background-color: white;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #7c3aed;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #1f2937;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .header p {
            color: #6b7280;
            font-size: 12px;
        }

        .filters {
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 25px;
            font-size: 12px;
        }

        .filters h3 {
            color: #374151;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 10px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 8px;
        }

        .filter-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 10px;
        }

        .filter-item {
            margin-bottom: 8px;
        }

        .filter-item strong {
            color: #1f2937;
            display: block;
            font-size: 11px;
            margin-bottom: 2px;
        }

        .filter-item span {
            color: #6b7280;
            display: block;
        }

        .main-cards {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            text-align: center;
        }

        .card.blue {
            border-top: 4px solid #3b82f6;
            background-color: #f0f9ff;
        }

        .card.red {
            border-top: 4px solid #dc2626;
            background-color: #fef2f2;
        }

        .card.green {
            border-top: 4px solid #10b981;
            background-color: #f0fdf4;
        }

        .card h3 {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .card p {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .card.blue p {
            color: #3b82f6;
        }

        .card.red p {
            color: #dc2626;
        }

        .card.green p {
            color: #10b981;
        }

        .card small {
            font-size: 10px;
            color: #6b7280;
        }

        .metrics {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }

        .metric-box {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 15px;
            background-color: #f9fafb;
        }

        .metric-box h4 {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
        }

        .metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 12px;
        }

        .metric-label {
            color: #6b7280;
        }

        .metric-value {
            font-weight: 700;
            color: #1f2937;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(to right, #3b82f6, #8b5cf6);
            border-radius: 4px;
        }

        .progress-fill.red {
            background: linear-gradient(to right, #dc2626, #ea580c);
        }

        .summary-table {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }

        .summary-table tr {
            border-bottom: 1px solid #e5e7eb;
        }

        .summary-table td {
            padding: 12px;
            font-size: 12px;
        }

        .summary-table td:first-child {
            color: #6b7280;
            font-weight: 500;
        }

        .summary-table td:last-child {
            text-align: right;
            font-weight: 700;
            color: #1f2937;
        }

        .info-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 12px;
            font-size: 11px;
            color: #1e3a8a;
            margin-bottom: 20px;
        }

        .footer {
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
            margin-top: 30px;
            font-size: 11px;
            color: #6b7280;
        }

        .footer-text {
            margin-bottom: 5px;
        }

        .generated-date {
            font-size: 10px;
            color: #9ca3af;
        }

        .text-positive {
            color: #10b981;
        }

        .text-negative {
            color: #dc2626;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📊 Financial Summary Report</h1>
            <p>Generated on {{ now()->format('M d, Y - H:i A') }}</p>
        </div>

        <!-- Filters Applied -->
        @if ((isset($filters['from']) && $filters['from']) || (isset($filters['to']) && $filters['to']))
            <div class="filters">
                <h3>Report Period</h3>
                <div class="filter-row">
                    @if (isset($filters['from']) && $filters['from'])
                        <div class="filter-item">
                            <strong>From Date</strong>
                            <span>{{ \Carbon\Carbon::parse($filters['from'])->format('M d, Y') }}</span>
                        </div>
                    @endif
                    @if (isset($filters['to']) && $filters['to'])
                        <div class="filter-item">
                            <strong>To Date</strong>
                            <span>{{ \Carbon\Carbon::parse($filters['to'])->format('M d, Y') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Main Financial Cards -->
        <div class="main-cards">
            <div class="card blue">
                <h3>Total Revenue</h3>
                <p>Rs {{ number_format($totalRevenue, 2) }}</p>
                <small>Income from all sources</small>
            </div>

            <div class="card red">
                <h3>Total Expenses</h3>
                <p>Rs {{ number_format($totalExpense, 2) }}</p>
                <small>Outflows and payables</small>
            </div>

            <div class="card {{ $netProfit >= 0 ? 'green' : 'red' }}">
                <h3>Net Profit/Loss</h3>
                <p class="{{ $netProfit >= 0 ? 'text-positive' : 'text-negative' }}">
                    Rs {{ number_format($netProfit, 2) }}
                </p>
                <small>{{ $netProfit >= 0 ? 'Positive balance' : 'Negative balance' }}</small>
            </div>
        </div>

        <!-- Info Box -->
        <div class="info-box">
            <strong>📌 Report Overview:</strong> This financial summary provides a comprehensive overview of your school's revenue, expenses, and net profit for the selected period. Use this data to analyze financial performance and make informed business decisions.
        </div>

        <!-- Financial Metrics -->
        <div class="metrics">
            <!-- Revenue to Expense Ratio -->
            <div class="metric-box">
                <h4>Financial Ratio Analysis</h4>
                <div class="metric-row">
                    <span class="metric-label">Revenue to Expense Ratio</span>
                    <span class="metric-value">
                        @if($totalExpense > 0)
                            {{ number_format($totalRevenue / $totalExpense, 2) }}:1
                        @else
                            ∞ (No expenses)
                        @endif
                    </span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: {{ min(($totalRevenue / max($totalRevenue, $totalExpense)) * 100, 100) }}%"></div>
                </div>
                <small style="color: #6b7280; display: block; margin-top: 5px;">Shows relationship between income and spending</small>
            </div>

            <!-- Budget Allocation -->
            <div class="metric-box">
                <h4>Budget Allocation</h4>
                <div class="metric-row">
                    <span class="metric-label">Expense Percentage</span>
                    <span class="metric-value">
                        @if($totalRevenue > 0)
                            {{ number_format(($totalExpense / $totalRevenue) * 100, 1) }}%
                        @else
                            0%
                        @endif
                    </span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill red" style="width: {{ min(($totalExpense / max($totalRevenue, 1)) * 100, 100) }}%"></div>
                </div>
                <small style="color: #6b7280; display: block; margin-top: 5px;">Percentage of revenue spent on expenses</small>
            </div>
        </div>

        <!-- Summary Table -->
        <table class="summary-table">
            <tbody>
                <tr>
                    <td>💰 Total Revenue</td>
                    <td>Rs {{ number_format($totalRevenue, 2) }}</td>
                </tr>
                <tr>
                    <td>💸 Total Expenses</td>
                    <td>Rs {{ number_format($totalExpense, 2) }}</td>
                </tr>
                <tr>
                    <td style="background-color: #f9fafb; font-weight: 700;">📈 Net Profit/Loss</td>
                    <td style="background-color: #f9fafb; {{ $netProfit >= 0 ? 'color: #10b981' : 'color: #dc2626' }}">
                        Rs {{ number_format($netProfit, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Additional Metrics -->
        <div class="metric-box" style="margin-bottom: 25px;">
            <h4>Detailed Breakdown</h4>
            <div class="metric-row">
                <span class="metric-label">Remaining Balance</span>
                <span class="metric-value {{ $netProfit >= 0 ? 'text-positive' : 'text-negative' }}">
                    Rs {{ number_format($netProfit, 2) }}
                </span>
            </div>
            <div class="metric-row">
                <span class="metric-label">Profit Margin</span>
                <span class="metric-value">
                    @if($totalRevenue > 0)
                        {{ number_format(($netProfit / $totalRevenue) * 100, 2) }}%
                    @else
                        0%
                    @endif
                </span>
            </div>
            <div class="metric-row">
                <span class="metric-label">Expense Ratio</span>
                <span class="metric-value">
                    @if($totalRevenue > 0)
                        {{ number_format(($totalExpense / $totalRevenue) * 100, 2) }}%
                    @else
                        0%
                    @endif
                </span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">This is an automatically generated report from School Management System</div>
            <div class="generated-date">Report ID: FIN-{{ now()->format('YmdHis') }}</div>
        </div>
    </div>
</body>
</html>
