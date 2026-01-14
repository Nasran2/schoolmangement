<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Payment Slip</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>window.onload = function() { window.print(); };</script>
    {!! $slipHeader !!}
</head>
<body class="bg-white font-sans text-gray-900 antialiased p-8">
    <div class="max-w-2xl mx-auto">
        <h2 class="text-2xl font-bold mb-1">Payment Slip</h2>
        <div class="text-gray-600 mb-6">{{ $schoolName ?? config('app.name') }}</div>

        <div class="border border-gray-200 rounded-lg p-6 space-y-2">
            <div class="grid grid-cols-3 gap-4">
                <div class="font-bold">Bill No:</div>
                <div class="col-span-2">{{ $item->bill_no }}</div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div class="font-bold">Paid Date:</div>
                <div class="col-span-2">{{ optional($item->paid_at)->format('d-m-Y') }}</div>
            </div>
            <div class="grid grid-cols-3 gap-4">
                <div class="font-bold">Category:</div>
                <div class="col-span-2">{{ $item->category?->name }}</div>
            </div>
            @if($item->student)
                <div class="grid grid-cols-3 gap-4">
                    <div class="font-bold">Admission No:</div>
                    <div class="col-span-2">{{ $item->student->admission_number }}</div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="font-bold">Student:</div>
                    <div class="col-span-2">{{ $item->student->name }}</div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="font-bold">Class/Year:</div>
                    <div class="col-span-2">{{ $item->student->classRoom?->name ?? $item->student->class }} / {{ $item->student->year }}</div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="font-bold">Guardian:</div>
                    <div class="col-span-2">{{ $item->student->guardian_name }} {{ $item->student->guardian_phone ? '(' . $item->student->guardian_phone . ')' : '' }}</div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="font-bold">Address:</div>
                    <div class="col-span-2">{{ $item->student->address }}</div>
                </div>
            @endif
            <div class="grid grid-cols-3 gap-4">
                <div class="font-bold">Amount:</div>
                <div class="col-span-2 font-bold text-lg">{{ number_format((float) $item->amount, 2) }}</div>
            </div>
            @php $isMonthly = $item->student && $item->student->monthlyFeeCategoryId() && $item->student->monthlyFeeCategoryId() == $item->revenue_category_id; @endphp
            @if($isMonthly)
                <div class="grid grid-cols-3 gap-4">
                    <div class="font-bold">Payment Type:</div>
                    <div class="col-span-2">Monthly Fee</div>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div class="font-bold">Payment Month:</div>
                    <div class="col-span-2">{{ optional($item->paid_at)->format('F Y') }}</div>
                </div>
            @endif
            @if($item->student)
                <div class="grid grid-cols-3 gap-4 border-t border-gray-100 pt-2 mt-2">
                    <div class="font-bold">Current Due:</div>
                    <div class="col-span-2 text-red-600 font-semibold">{{ number_format((float) $item->student->computed_due_amount, 2) }} <span class="text-xs text-gray-500 font-normal">(after payment)</span></div>
                </div>
            @endif
            @if($item->notes)
                <div class="grid grid-cols-3 gap-4 border-t border-gray-100 pt-2 mt-2">
                    <div class="font-bold">Notes:</div>
                    <div class="col-span-2 text-gray-600 italic">{{ $item->notes }}</div>
                </div>
            @endif
        </div>

        <div class="mt-6">
            {!! $slipFooter !!}
        </div>
    </div>
</body>
</html>
