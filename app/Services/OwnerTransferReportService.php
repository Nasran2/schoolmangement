<?php

namespace App\Services;

use App\Models\OwnerTransfer;
use App\Models\OwnerTransferReturn;

class OwnerTransferReportService
{
    public function forPeriod(?string $from, ?string $to): array
    {
        $period = function ($query, string $column) use ($from, $to) {
            return $query->when($from, fn ($q) => $q->whereDate($column, '>=', $from))
                ->when($to, fn ($q) => $q->whereDate($column, '<=', $to))->orderBy($column)->orderBy('id');
        };
        $transfers = $period(OwnerTransfer::withSum('returns', 'amount'), 'transferred_at')->get();
        $returns = $period(OwnerTransferReturn::with('transfer'), 'returned_at')->get();
        $gross = round((float) $transfers->sum('effective_amount'), 2);
        $returned = round((float) $returns->sum('effective_amount'), 2);

        return ['transfers' => $transfers, 'returns' => $returns, 'gross' => $gross, 'returned' => $returned, 'net' => round($gross - $returned, 2)];
    }
}
