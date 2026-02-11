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
            font-family: Georgia, 'Times New Roman', serif;
            color: #222;
            background-color: #ffffff;
            line-height: 1.5;
        }

        .container {
            max-width: 100%;
            margin: 0;
            padding: 20px;
            background-color: white;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #b8860b;
            padding: 15px 0;
            margin-bottom: 20px;
        }
        .header-left {
            display: flex;
            align-items: center;
        }
        .header-left .logo {
            height: 65px;
            margin-right: 20px;
            object-fit: contain;
            width: auto;
            max-width: 80px;
        }
        .header-left h1 {
            color: #002147;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 0.02em;
            margin-bottom: 4px;
            text-transform: uppercase;
            font-family: 'Times New Roman', serif;
        }
        .header-right {
            text-align: right;
        }
        .header-right .subhead {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
        }
        .header-right p {
            font-size: 10px;
            color: #374151;
        }
        .header-right .generated-date {
            font-size: 9px;
            color: #9ca3af;
        }
        .subhead { font-size: 11px; color: #555; }

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
            display: table;
            width: 100%;
            table-layout: fixed;
            margin-top: 10px;
        }

        .filter-item {
            display: table-cell;
            width: 50%;
            padding-right: 15px;
            vertical-align: top;
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

        .day { margin-bottom: 18px; page-break-inside: avoid; }
        .day-title { font-weight: 700; margin-bottom: 6px; }
        .columns { width: 100%; border-collapse: collapse; }
        .columns > div { width: 48%; display: inline-block; vertical-align: top; }
        .columns > div:last-child { margin-left: 2%; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { font-size: 11px; padding: 6px; border-bottom: 1px solid #e5e7eb; }
        .table th { text-align: left; color: #6b7280; }
        .amount { text-align: right; }
        .summary { width: 100%; margin-top: 8px; font-size: 11px; border-top: 1px dotted #ccc; padding-top: 4px; }
        .summary-left { width: 50%; float: left; }
        .summary-right { width: 50%; float: right; text-align: right; }
        .summary:after { content: ""; display: table; clear: both; }
        .closing { font-weight: 800; }

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
            width: 100%;
            margin-bottom: 25px;
        }
        .metrics:after { content: ""; display: table; clear: both; }

        .metric-box {
            width: 47%;
            float: left;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 15px;
            background-color: #f9fafb;
            margin-bottom: 10px;
        }
        .metric-box:last-child { float: right; }

        .metric-box h4 {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 10px;
        }

        .metric-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .metric-table td {
            padding-bottom: 8px;
            vertical-align: top;
        }
        .metric-table .label {
            color: #6b7280;
            text-align: left;
        }
        .metric-table .value {
            font-weight: 700;
            color: #1f2937;
            text-align: right;
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
            <div class="header-left">
                @if (!empty($school['logo']))
                    <img class="logo" src="{{ public_path('storage/' . $school['logo']) }}" alt="Logo">
                @endif
                <div style="padding-top: 2px;">
                    @if(!empty($school['address']))
                        <h1 style="font-size: 16px; margin-bottom: 0; color: #002147;">{{ strtoupper($school['address']) }}</h1>
                    @endif
                    <h1 style="margin-bottom: 4px; color: #002147; font-size: 22px; font-weight: bold; text-transform: uppercase;">{{ strtoupper($school['name'] ?? 'SCHOOL') }}</h1>
                    <div class="subhead" style="font-size: 11px; color: #555;">Tel: {{ $school['phone'] ?? '' }}</div>
                </div>
            </div>
            <div class="header-right" style="text-align: right;">
                <div class="subhead" style="font-size: 13px; font-weight: 600; color: #374151;">{{ !empty($daily) ? 'Financial Daily Ledger' : 'Financial Ledger (Aggregated)' }}</div>
                <p style="font-size: 10px; color: #374151;">Generated on {{ now()->format('M d, Y') }}</p>
                <p style="font-size: 9px; color: #9ca3af;">{{ now()->format('H:i A') }}</p>
            </div>
        </div>

        <!-- Filters Applied -->
        @if ((isset($filters['from']) && $filters['from']) || (isset($filters['to']) && $filters['to']) || (isset($filters['method']) && $filters['method'] && $filters['method'] !== 'all'))
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

                @if (isset($filters['method']) && $filters['method'] && $filters['method'] !== 'all')
                    @php
                        $label = $filters['method'] === 'bank' ? 'Bank (Transfer + Cheque)'
                            : ($filters['method'] === 'bank_transfer' ? 'Bank Transfer'
                            : ($filters['method'] === 'cheque' ? 'Cheque'
                            : ($filters['method'] === 'cash' ? 'Cash' : (string) $filters['method'])));
                    @endphp
                    <div class="filter-row">
                        <div class="filter-item">
                            <strong>Method</strong>
                            <span>{{ $label }}</span>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        @foreach($days as $d)
            <div class="day">
                @if(!empty($daily))
                    <div class="day-title">Date: {{ $d['date']->format('M d, Y') }}</div>
                @else
                    <div class="day-title">Period: {{ \Carbon\Carbon::parse($filters['from'] ?? $d['date'])->format('M d, Y') }} — {{ \Carbon\Carbon::parse($filters['to'] ?? $d['date'])->format('M d, Y') }}</div>
                @endif
                <div class="columns">
                    <!-- Debit -->
                    <div>
                        <div style="font-weight:700; margin-bottom:4px;">Debit (Income)</div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th>Folio</th>
                                    <th class="amount">Amount (Rs)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($d['debits'] as $row)
                                    <tr>
                                        <td>{{ $row['description'] }}</td>
                                        <td>{{ $row['ref'] ?? '—' }}</td>
                                        <td class="amount">{{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td style="font-weight:700;">Total Debit</td>
                                    <td></td>
                                    <td class="amount" style="font-weight:700;">{{ number_format($d['opening'] + $d['income_total'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Credit -->
                    <div>
                        <div style="font-weight:700; margin-bottom:4px;">Credit (Expense)</div>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="amount">Amount (Rs)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($d['credits'] as $row)
                                    <tr>
                                        <td>{{ $row['description'] }}</td>
                                        <td class="amount">{{ number_format($row['amount'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td>No expenses</td>
                                        <td class="amount">0.00</td>
                                    </tr>
                                @endforelse
                                <tr>
                                    <td style="font-weight:700;">Total Credit</td>
                                    <td class="amount" style="font-weight:700;">{{ number_format($d['expense_total'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="summary">
                    <div class="summary-left">B.B.F (Opening): <strong>Rs {{ number_format($d['opening'], 2) }}</strong></div>
                    <div class="summary-right"><span class="closing">Closing Balance: Rs {{ number_format($d['closing'], 2) }}</span></div>
                </div>
            </div>
        @endforeach

        <!-- Info Box -->
        <div class="info-box">
            <strong> Report Overview:</strong> This financial summary provides a comprehensive overview of your school's revenue, expenses, and net profit for the selected period. Use this data to analyze financial performance and make informed business decisions.
        </div>

        <!-- Summary Table -->
        <table class="summary-table">
            <tbody>
                <tr>
                    <td>Total Revenue</td>
                    <td>Rs {{ number_format($totalRevenue, 2) }}</td>
                </tr>
                <tr>
                    <td>Total Expenses</td>
                    <td>Rs {{ number_format($totalExpense, 2) }}</td>
                </tr>
                <tr>
                    <td style="background-color: #f9fafb; font-weight: 700;">Net Profit/Loss</td>
                    <td style="background-color: #f9fafb; {{ $netProfit >= 0 ? 'color: #10b981' : 'color: #dc2626' }}">
                        Rs {{ number_format($netProfit, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Additional Metrics -->
        <div style="clear: both; margin-bottom: 25px; border: 1px solid #e5e7eb; border-radius: 6px; padding: 15px; background-color: #f9fafb;">
            <h4>Detailed Breakdown</h4>
            <table class="metric-table">
                <tr>
                    <td class="label">Remaining Balance</td>
                    <td class="value {{ $netProfit >= 0 ? 'text-positive' : 'text-negative' }}">
                        Rs {{ number_format($netProfit, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="label">Profit Margin</td>
                    <td class="value">
                        @if($totalRevenue > 0)
                            {{ number_format(($netProfit / $totalRevenue) * 100, 2) }}%
                        @else
                            0%
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="label">Expense Ratio</td>
                    <td class="value">
                        @if($totalRevenue > 0)
                            {{ number_format(($totalExpense / $totalRevenue) * 100, 2) }}%
                        @else
                            0%
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="footer" style="clear: both;">
            <div class="footer-text">Generated by School Management System</div>
            <div class="generated-date">Report ID: FIN-{{ now()->format('YmdHis') }}</div>
        </div>
    </div>
</body>
</html>
