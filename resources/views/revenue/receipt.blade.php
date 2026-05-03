<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Payment Receipt</h2>
            <div class="flex gap-3">
                <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-white shadow-sm hover:bg-indigo-700">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z" />
                    </svg>
                    Print Receipt
                </button>
                <a href="{{ route('revenue.items.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-sm text-gray-700 shadow-sm hover:bg-gray-50">
                    Back to Revenue
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gradient-to-b from-slate-50 to-white">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @php
                $isReturnedChequeReceipt = ($revenue->payment_method ?? null) === 'cheque' && ($revenue->payment_status ?? null) === 'rejected';
            @endphp

            <div class="mb-6 {{ $isReturnedChequeReceipt ? 'bg-rose-50 border-rose-200' : 'bg-green-50 border-green-200' }} border rounded-lg p-4">
                <div class="flex items-center gap-3">
                    @if($isReturnedChequeReceipt)
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-rose-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" />
                        </svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 text-green-600">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @endif
                    <div>
                        <p class="font-semibold {{ $isReturnedChequeReceipt ? 'text-rose-900' : 'text-green-900' }}">
                            {{ $isReturnedChequeReceipt ? 'Payment Cancelled' : 'Payment Successful!' }}
                        </p>
                        <p class="text-sm {{ $isReturnedChequeReceipt ? 'text-rose-700' : 'text-green-700' }}">
                            Receipt #{{ $revenue->bill_no }} {{ $isReturnedChequeReceipt ? 'belongs to a returned cheque and is not counted as paid.' : 'has been generated' }}
                        </p>
                    </div>
                </div>
            </div>

            <x-receipt 
                :revenue="$revenue"
                :student="$revenue->student"
                :category="$revenue->category"
                :schoolInfo="$schoolInfo"
                :schoolLogoDataUri="$schoolLogoDataUri"
            />
        </div>
    </div>

    @if($autoPrint)
        <script>
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            });
        </script>
    @endif
</x-app-layout>
