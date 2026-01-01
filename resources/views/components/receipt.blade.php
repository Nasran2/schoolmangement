@props(['revenue', 'student', 'category', 'schoolInfo'])

@php
// Simple number to words (en) helper used for the "sum of" line
if (!function_exists('numberToWords')) {
    function numberToWords($number) {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $number = floor((float) $number);
        if ($number == 0) return 'Zero';
        $words = '';
        if ($number >= 100000) {
            $words .= numberToWords(floor($number / 100000)) . ' Lakh ';
            $number %= 100000;
        }
        if ($number >= 1000) {
            $words .= numberToWords(floor($number / 1000)) . ' Thousand ';
            $number %= 1000;
        }
        if ($number >= 100) {
            $words .= $ones[floor($number / 100)] . ' Hundred ';
            $number %= 100;
        }
        if ($number >= 20) {
            $words .= $tens[floor($number / 10)] . ' ';
            $number %= 10;
        }
        if ($number > 0) {
            $words .= $ones[$number] . ' ';
        }
        return trim($words);
    }
}

$paidAt = $revenue->paid_at ?: now();
$year = $paidAt->format('Y');
$monthShort = $paidAt->format('M');
$months = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
$amount = (float) ($revenue->amount ?? 0);
$amountWords = ucwords(numberToWords($amount)) . ' Rupees Only';
// Fee type helpers based on category.payment_type
$paymentType = strtolower($category->payment_type ?? '');
$isMonthly = $paymentType === 'monthly';
$isAdmission = $paymentType === 'admission';
$isFacilities = $paymentType === 'facilities';
$isTerm = in_array($paymentType, ['term','semester','term_fee']);
$
$schoolName = $schoolInfo['name'] ?? config('app.name');
$schoolAddress = $schoolInfo['address'] ?? '';
$schoolPhone = $schoolInfo['phone'] ?? '';
@endphp

<div id="receipt-print" class="bg-white p-8 max-w-3xl mx-auto" style="font-family: 'Courier New', monospace;">
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-2xl font-extrabold uppercase">{{ $schoolName }}</h1>
            @if($schoolAddress)
                <p class="text-sm">{{ $schoolAddress }}</p>
            @endif
            @if($schoolPhone)
                <p class="text-sm">Tel: {{ $schoolPhone }}</p>
            @endif
        </div>
        <div class="text-right">
            <div class="border border-gray-800 px-3 py-2 inline-block">
                <span class="font-bold mr-2">Receipt No</span>
                <span>{{ $revenue->bill_no ?? 'N/A' }}</span>
            </div>
            <div class="mt-2">
                <span class="font-bold">Date:</span>
                <span class="inline-block border-b border-gray-800 min-w-[140px] text-center">{{ $paidAt->format('d/m/Y') }}</span>
            </div>
        </div>
    </div>

    <div class="mt-4 space-y-2 text-sm">
        <div>
            <span class="font-semibold">Received with thanks from Master/Ms</span>
            <span class="inline-block border-b border-gray-800 min-w-[220px]">{{ $student?->name }}</span>
            <span class="ml-6 font-semibold">Mr/Mrs.Ms</span>
            <span class="inline-block border-b border-gray-800 min-w-[220px]">{{ $student?->guardian_name }}</span>
        </div>
        <div>
            <span class="inline-block border-b border-gray-800 min-w-[300px]">{{ $student?->address }}</span>
            <span class="ml-3">(Address)</span>
        </div>
        <div>
            <span class="mr-2">the sum</span>
            <span class="inline-block border-b border-gray-800 min-w-[120px]">Rs {{ number_format($amount,2) }}</span>
            <span class="mx-2">of</span>
            <span class="inline-block border-b border-gray-800 min-w-[360px]">{{ $amountWords }}</span>
        </div>
        <div>
            <span class="mr-2">Being payment for</span>
            <span class="inline-block border-b border-gray-800 min-w-[360px]">{{ $category->name ?? 'Fees' }}</span>
        </div>
    </div>

    @if($isMonthly)
        <div class="mt-4">
            <div class="flex items-center gap-2 text-xs">
                @foreach($months as $m)
                    @php $active = ($m === strtoupper($monthShort)); @endphp
                    <div class="flex items-center">
                        <div class="w-5 h-5 border border-gray-800 text-center leading-5 {{ $active ? 'bg-gray-900 text-white' : '' }}">{{ $active ? 'X' : '' }}</div>
                        <div class="ml-1 mr-3">{{ $m }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($isTerm)
        <div class="mt-4">
            <div class="flex items-center gap-2 text-xs">
                <span class="ml-4 font-semibold">Term Fee</span>
                <div class="w-6 h-6 border border-gray-800 ml-2"></div>
                <div class="w-6 h-6 border border-gray-800 ml-2"></div>
                <div class="w-6 h-6 border border-gray-800 ml-2"></div>
                <span class="ml-3">{{ substr($year,0,2) }}<span class="inline-block border-b border-gray-800 min-w-[30px] ml-1">{{ substr($year,2,2) }}</span></span>
            </div>
        </div>
    @endif

    <div class="mt-4 text-sm">
        <div class="grid grid-cols-2 gap-6">
            <div class="space-y-2">
                @if($isAdmission)
                    <div class="flex justify-between"><span>I. Admission fee :</span><span class="inline-block min-w-[140px] text-right">Rs {{ number_format($amount,2) }}</span></div>
                @endif
                @if($isMonthly)
                    <div class="flex justify-between"><span>{{ $isAdmission ? 'II.' : 'I.' }} Monthly fee :</span><span class="inline-block min-w-[140px] text-right">Rs {{ number_format($amount,2) }}</span></div>
                @endif
                @if($isFacilities)
                    <div class="flex justify-between"><span>{{ ($isAdmission || $isMonthly) ? 'III.' : 'I.' }} Facilities fee :</span><span class="inline-block min-w-[140px] text-right">Rs {{ number_format($amount,2) }}</span></div>
                @endif
                @if(!$isAdmission && !$isMonthly && !$isFacilities)
                    <div class="flex justify-between"><span>{{ $category->name }} :</span><span class="inline-block min-w-[140px] text-right">Rs {{ number_format($amount,2) }}</span></div>
                @endif
                <div class="flex justify-between font-semibold"><span>Total :</span><span class="inline-block min-w-[140px] text-right">Rs {{ number_format($amount,2) }}</span></div>
            </div>
            <div class="space-y-2">
                <div class="flex items-center gap-4"><span>Grade</span><span class="inline-block border-b border-gray-800 min-w-[120px]">{{ $student?->classRoom?->name ?? $student?->class }}</span></div>
                <div class="flex items-center gap-4"><span>Std.reg.No.</span><span class="inline-block border-b border-gray-800 min-w-[120px]">{{ $student?->admission_number }}</span></div>
                <div class="flex items-center gap-6 mt-2">
                    <div class="flex items-center gap-2"><span>Junior</span><div class="w-6 h-6 border border-gray-800"></div></div>
                    <div class="flex items-center gap-2 ml-6"><span>Senior</span><div class="w-6 h-6 border border-gray-800"></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-8 flex justify-end">
        <div class="text-center">
            <div class="border-t border-gray-800 pt-1 w-56">
                <p class="text-xs">Authorized Signature | Cashier</p>
            </div>
        </div>
    </div>

    <div class="text-center mt-6 text-xs text-gray-600">This is a computer-generated receipt</div>
</div>

<style media="print">
    @page { size: A4; margin: 10mm; }
    body * { visibility: hidden; }
    #receipt-print, #receipt-print * { visibility: visible; }
    #receipt-print { position: absolute; left: 0; top: 0; width: 100%; }
</style>
