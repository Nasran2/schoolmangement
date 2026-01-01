<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Student Payment Slip</title>
    <style>
        body { font-family: ui-sans-serif, system-ui; }
        .container { max-width: 640px; margin: 0 auto; }
        .section { margin-top: 16px; }
        .muted { color: #4b5563; }
        .border { border: 1px solid #e5e7eb; padding: 12px; border-radius: 6px; }
    </style>
    <script>window.onload = function() { window.print(); };</script>
    {!! $slipHeader !!}
</head>
<body>
<div class="container">
    <h2>Payment Slip</h2>
    <div class="muted">{{ $schoolName ?? config('app.name') }}</div>

    <div class="section border">
        <div><strong>Bill No:</strong> {{ $item->bill_no }}</div>
        <div><strong>Paid Date:</strong> {{ optional($item->paid_at)->format('Y-m-d') }}</div>
        <div><strong>Category:</strong> {{ $item->category?->name }}</div>
        @if($item->student)
            <div><strong>Admission No:</strong> {{ $item->student->admission_number }}</div>
            <div><strong>Student:</strong> {{ $item->student->name }}</div>
            <div><strong>Class/Year:</strong> {{ $item->student->classRoom?->name ?? $item->student->class }} / {{ $item->student->year }}</div>
            <div><strong>Guardian:</strong> {{ $item->student->guardian_name }} {{ $item->student->guardian_phone ? '(' . $item->student->guardian_phone . ')' : '' }}</div>
            <div><strong>Address:</strong> {{ $item->student->address }}</div>
        @endif
        <div><strong>Amount:</strong> {{ number_format((float) $item->amount, 2) }}</div>
        @php $isMonthly = $item->student && $item->student->monthlyFeeCategoryId() && $item->student->monthlyFeeCategoryId() == $item->revenue_category_id; @endphp
        @if($isMonthly)
            <div><strong>Payment Type:</strong> Monthly Fee</div>
            <div><strong>Payment Month:</strong> {{ optional($item->paid_at)->format('F Y') }}</div>
        @endif
        @if($item->student)
            <div><strong>Current Due (after payment):</strong> {{ number_format((float) $item->student->computed_due_amount, 2) }}</div>
        @endif
        <div><strong>Notes:</strong> {{ $item->notes }}</div>
    </div>

    <div class="section">
        {!! $slipFooter !!}
    </div>
</div>
</body>
</html>
