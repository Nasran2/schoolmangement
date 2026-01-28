<?php

namespace App\Services\Billing;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class BillNumberService
{
    public function peekNextRevenueBillNumber(): string
    {
        $prefix = app('settings')->get('billing.revenue.prefix', 'BILL-');
        $startNumber = (int) app('settings')->get('billing.revenue.start_number', '1000');
        $row = Setting::query()->where('key', 'billing.revenue.next_number')->first();
        $next = $row ? (int) ($row->value ?? $startNumber) : $startNumber;

        return $prefix.(string) $next;
    }

    public function nextRevenueBillNumber(): string
    {
        return DB::transaction(function () {
            $prefix = app('settings')->get('billing.revenue.prefix', 'BILL-');

            $startNumber = (int) app('settings')->get('billing.revenue.start_number', '1000');

            $row = Setting::query()
                ->where('key', 'billing.revenue.next_number')
                ->lockForUpdate()
                ->first();

            $next = $row ? (int) ($row->value ?? $startNumber) : $startNumber;

            Setting::query()->updateOrCreate(
                ['key' => 'billing.revenue.next_number'],
                ['value' => (string) ($next + 1), 'group' => 'general']
            );

            return $prefix.(string) $next;
        });
    }
}
