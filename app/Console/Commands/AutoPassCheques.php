<?php

namespace App\Console\Commands;

use App\Models\Revenue;
use Illuminate\Console\Command;

class AutoPassCheques extends Command
{
    protected $signature = 'cheques:auto-pass {--days= : Auto-pass after N days from cheque date (default from settings or 14)}';

    protected $description = 'Automatically mark hold cheque revenues as passed after the configured number of days from cheque date.';

    public function handle(): int
    {
        $days = $this->option('days');
        if ($days === null || $days === '') {
            try {
                $days = (int) (app('settings')->get('cheques.auto_pass_days', '14') ?: 14);
            } catch (\Throwable $e) {
                $days = 14;
            }
        }

        $days = (int) $days;
        if ($days < 1) {
            $days = 14;
        }

        $cutoffDate = now()->subDays($days)->toDateString();

        $updated = Revenue::query()
            ->where('payment_method', 'cheque')
            ->whereIn('payment_status', ['hold', 'pending'])
            ->whereNotNull('cheque_date')
            ->whereDate('cheque_date', '<=', $cutoffDate)
            ->update([
                'payment_status' => 'confirmed',
                'confirmed_at' => now(),
                'paid_at' => now()->toDateString(),
            ]);

        $this->info('Auto-passed cheques: '.$updated);

        return self::SUCCESS;
    }
}
