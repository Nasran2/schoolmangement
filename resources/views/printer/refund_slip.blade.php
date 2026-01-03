<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Refund Slip</title>
    <style>
        @page { size: A5 landscape; margin: 0; }
        body { 
            font-family: 'Courier New', Courier, monospace; 
            margin: 20px;
            color: #000;
            font-size: 14px;
            line-height: 1.4;
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            border: 2px solid #000;
            padding: 20px;
            position: relative;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
        }
        .school-name {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .slip-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
            display: inline-block;
            border: 1px solid #000;
            padding: 5px 15px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .col {
            flex: 1;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 140px;
        }
        .value {
            display: inline-block;
        }
        .section-title {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 15px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .amount-box {
            border: 2px solid #000;
            padding: 10px;
            text-align: center;
            margin-top: 20px;
            font-size: 16px;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            text-align: center;
            padding-top: 5px;
            font-size: 12px;
        }
        .meta {
            font-size: 10px;
            text-align: center;
            margin-top: 20px;
            color: #555;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        @media print {
            body { margin: 0; }
            .container { border: none; }
        }
    </style>
    <script>window.onload = function() { window.print(); };</script>
    {!! $slipHeader !!}
</head>
<body>
<div class="container">
    <div class="header">
        <div class="school-name">{{ $schoolName ?? config('app.name') }}</div>
        <div>{{ app('settings')->get('school.address', 'School Address') }}</div>
        <div>{{ app('settings')->get('school.phone', '') }}</div>
        <div class="slip-title">Refund Slip</div>
    </div>

    @php
        $item = $adjustment->revenue;
        $refundedSoFar = 0.0;
        if ($item) {
            try {
                $refundedSoFar = (float) \App\Models\RevenueAdjustment::query()
                    ->where('revenue_id', $item->id)
                    ->where('type', 'refund')
                    ->sum('amount');
            } catch (\Exception $e) {
                $refundedSoFar = 0.0;
            }
        }
        $originalAmount = (float) ($item?->amount ?? 0);
        $netCollected = max(0, $originalAmount - $refundedSoFar);
    @endphp

    <div class="row">
        <div class="col">
            <div><span class="label">Refund Date:</span> <span class="value">{{ optional($adjustment->created_at)->format('Y-m-d H:i') }}</span></div>
            <div><span class="label">Processed By:</span> <span class="value">{{ $adjustment->creator?->name ?? '-' }}</span></div>
        </div>
        <div class="col text-right">
            <div><span class="label">Ref ID:</span> <span class="value">#{{ $adjustment->id }}</span></div>
        </div>
    </div>

    <div class="section-title">Student & Bill Details</div>
    
    <table>
        <tr>
            <th width="20%">Bill No</th>
            <td width="30%">{{ $item?->bill_no ?? '-' }}</td>
            <th width="20%">Admission No</th>
            <td width="30%">{{ $item->student->admission_number ?? '-' }}</td>
        </tr>
        <tr>
            <th>Student Name</th>
            <td colspan="3">{{ $item->student->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>Class / Year</th>
            <td>{{ $item->student->classRoom?->name ?? $item->student->class ?? '-' }} / {{ $item->student->year ?? '-' }}</td>
            <th>Category</th>
            <td>{{ $item?->category?->name ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Refund Information</div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Amount (Rs)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong>Refund Issued</strong><br>
                    <small>Reason: {{ $adjustment->reason ?: 'No reason provided' }}</small>
                </td>
                <td class="text-right" style="font-size: 16px; font-weight: bold;">
                    {{ number_format((float) $adjustment->amount, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 20px; border-top: 1px dashed #000; padding-top: 10px;">
        <div class="row">
            <div class="col">
                <div><span class="label">Original Bill Amount:</span> Rs {{ number_format($originalAmount, 2) }}</div>
                <div><span class="label">Total Refunded:</span> Rs {{ number_format($refundedSoFar, 2) }}</div>
            </div>
            <div class="col text-right">
                <div style="font-size: 16px; font-weight: bold;">
                    Net Collected: Rs {{ number_format($netCollected, 2) }}
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="signature-line">
            Prepared By
        </div>
        <div class="signature-line">
            Authorized Signature
        </div>
        <div class="signature-line">
            Receiver Signature
        </div>
    </div>

    <div class="meta">
        This is a computer-generated document. | Printed on {{ now()->format('Y-m-d H:i:s') }}
    </div>

    <div class="section">
        {!! $slipFooter !!}
    </div>
</div>
</body>
</html>
