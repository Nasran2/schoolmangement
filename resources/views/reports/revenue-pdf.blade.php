<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Report</title>
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
            border-bottom: 3px solid #3b82f6;
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
            grid-template-columns: 1fr 1fr 1fr;
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

        .summary {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }

        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }

        .summary-card.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .summary-card.blue {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }

        .summary-card h4 {
            font-size: 12px;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .summary-card p {
            font-size: 20px;
            font-weight: 700;
        }

        .table-section {
            margin-top: 20px;
        }

        .table-section h3 {
            color: #1f2937;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        thead {
            background-color: #f3f4f6;
            border-bottom: 2px solid #d1d5db;
        }

        thead tr th {
            padding: 10px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #374151;
            border: 1px solid #e5e7eb;
        }

        tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }

        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        tbody tr td {
            padding: 10px;
            font-size: 11px;
            color: #4b5563;
            border: 1px solid #e5e7eb;
        }

        .amount {
            text-align: right;
            font-weight: 600;
            color: #10b981;
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

        .no-data {
            text-align: center;
            padding: 30px;
            color: #9ca3af;
            font-size: 12px;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
        }

        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📊 Revenue Report</h1>
            <p>Generated on {{ now()->format('M d, Y - H:i A') }}</p>
        </div>

        <!-- Filters Applied -->
        @if ((isset($filters['from']) && $filters['from']) || (isset($filters['to']) && $filters['to']) || (isset($filters['category_id']) && $filters['category_id']))
            <div class="filters">
                <h3>Applied Filters</h3>
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
                    @if (isset($filters['category_id']) && $filters['category_id'])
                        <div class="filter-item">
                            <strong>Category</strong>
                            <span>
                                @php
                                    $selectedCategory = $categories->find($filters['category_id']);
                                @endphp
                                {{ $selectedCategory?->name ?? 'N/A' }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Summary Cards -->
        <div class="summary">
            <div class="summary-card blue">
                <h4>Total Transactions</h4>
                <p>{{ $items->count() }}</p>
            </div>
            <div class="summary-card green">
                <h4>Total Revenue</h4>
                <p>Rs {{ number_format($totalAmount, 2) }}</p>
            </div>
            <div class="summary-card">
                <h4>Average Amount</h4>
                <p>Rs {{ number_format($items->avg('amount') ?? 0, 2) }}</p>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="table-section">
            <h3>Revenue Transactions</h3>
            @if ($items->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th style="width: 12%;">Bill No</th>
                            <th style="width: 12%;">Date</th>
                            <th style="width: 15%;">Category</th>
                            <th style="width: 20%;">Student</th>
                            <th style="width: 15%; text-align: right;">Amount</th>
                            <th style="width: 26%;">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $item->bill_no }}</td>
                                <td>
                                    <span class="badge badge-info">
                                        {{ optional($item->paid_at)->format('M d, Y') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-success">
                                        {{ $item->category?->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>{{ $item->student?->name ?? 'N/A' }}</td>
                                <td class="amount">Rs {{ number_format($item->amount, 2) }}</td>
                                <td>{{ $item->notes ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="no-data">
                    No revenue records found matching your criteria.
                </div>
            @endif
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">This is an automatically generated report from School Management System</div>
            <div class="generated-date">Report ID: REV-{{ now()->format('YmdHis') }}</div>
        </div>
    </div>
</body>
</html>
