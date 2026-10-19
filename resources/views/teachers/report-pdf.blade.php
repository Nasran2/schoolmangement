<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Teacher Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            font-size: 18px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Teacher Report ({{ $selectedMonth === 'all' ? 'All Months' : \Carbon\Carbon::parse($selectedMonth)->format('F Y') }})</h2>
        <p>Generated on: {{ now()->format('Y-m-d H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                @if(in_array('name', $columns))
                    <th>Teacher Name</th>
                @endif
                @if(in_array('contact', $columns))
                    <th>Contact Number</th>
                @endif
                @if(in_array('address', $columns))
                    <th>Address</th>
                @endif
                @if(in_array('basic_salary', $columns))
                    <th class="text-right">Basic Salary</th>
                @endif
                @if(in_array('extra_paid', $columns))
                    <th class="text-right">Extra Paid Amount</th>
                @endif
                @if(in_array('deduction', $columns))
                    <th class="text-right">Deduction</th>
                @endif
                @if(in_array('paid_month_date', $columns))
                    <th class="text-center">Paid Month Date</th>
                @endif
                @if(in_array('paid_amount', $columns))
                    <th class="text-right">Paid Amount</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($reportData as $row)
                <tr>
                    @if(in_array('name', $columns))
                        <td>{{ $row['name'] }}</td>
                    @endif
                    @if(in_array('contact', $columns))
                        <td>{{ $row['contact'] ?: '-' }}</td>
                    @endif
                    @if(in_array('address', $columns))
                        <td>{{ $row['address'] ?: '-' }}</td>
                    @endif
                    @if(in_array('basic_salary', $columns))
                        <td class="text-right">Rs {{ number_format($row['basic_salary'], 2) }}</td>
                    @endif
                    @if(in_array('extra_paid', $columns))
                        <td class="text-right">
                            @forelse($row['payment_details'] as $detail)
                                <div style="margin-bottom: 2px;">{{ $detail['month_name'] }} {{ number_format($detail['extra'], 0) }}</div>
                            @empty
                                -
                            @endforelse
                        </td>
                    @endif
                    @if(in_array('deduction', $columns))
                        <td class="text-right">
                            @forelse($row['payment_details'] as $detail)
                                <div style="margin-bottom: 2px;">{{ $detail['month_name'] }} {{ number_format($detail['deduction'], 0) }}</div>
                            @empty
                                -
                            @endforelse
                        </td>
                    @endif
                    @if(in_array('paid_month_date', $columns))
                        <td class="text-center">
                            @forelse($row['payment_details'] as $detail)
                                <div style="margin-bottom: 2px;">{{ $detail['date_str'] }}</div>
                            @empty
                                -
                            @endforelse
                        </td>
                    @endif
                    @if(in_array('paid_amount', $columns))
                        <td class="text-right">
                            @if(!$row['is_paid'])
                                <span style="color: red; font-size: 10px; text-transform: uppercase;">Not Paid</span>
                            @else
                                @foreach($row['payment_details'] as $detail)
                                    <div style="margin-bottom: 2px;">{{ $detail['month_name'] }} {{ number_format($detail['paid_amount'], 0) }}</div>
                                @endforeach
                            @endif
                        </td>
                    @endif
                </tr>
            @endforeach
            
            @if(count($reportData) === 0)
                <tr>
                    <td colspan="{{ count($columns) }}" class="text-center">No teachers found.</td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr>
                @php
                    $headerCols = 0;
                    if(in_array('name', $columns)) $headerCols++;
                    if(in_array('contact', $columns)) $headerCols++;
                    if(in_array('address', $columns)) $headerCols++;
                @endphp
                @if($headerCols > 0)
                    <th colspan="{{ $headerCols }}" class="text-right">Grand Totals</th>
                @endif
                @if(in_array('basic_salary', $columns))
                    <th class="text-right">Rs {{ number_format($grandTotalBasic, 2) }}</th>
                @endif
                @if(in_array('extra_paid', $columns))
                    <th class="text-right">Rs {{ number_format($grandTotalExtra, 2) }}</th>
                @endif
                @if(in_array('deduction', $columns))
                    <th class="text-right">Rs {{ number_format($grandTotalDeduction, 2) }}</th>
                @endif
                @if(in_array('paid_month_date', $columns))
                    <th></th>
                @endif
                @if(in_array('paid_amount', $columns))
                    <th class="text-right">Rs {{ number_format($grandTotalPaid, 2) }}</th>
                @endif
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        {{ config('app.name', 'School Management System') }} - Teacher Report
    </div>
</body>
</html>
