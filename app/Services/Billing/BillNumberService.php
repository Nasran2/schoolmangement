<?php

namespace App\Services\Billing;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class BillNumberService
{
    public function nextRevenueBillNumber(): string
    {
        return DB::transaction(function () {
            $prefix = app('settings')->get('billing.revenue.prefix', 'BILL-');
            $auto = app('settings')->get('billing.revenue.autogenerate', '1') === '1';

            if (! $auto) {
                return '';
            }

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
