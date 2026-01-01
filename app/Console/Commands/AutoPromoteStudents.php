<?php

namespace App\Console\Commands;

use App\Services\PromotionService;
use Illuminate\Console\Command;

class AutoPromoteStudents extends Command
{
    protected $signature = 'school:auto-promote';
    protected $description = 'Automatically promote students based on configured month/day';

    public function handle(PromotionService $service): int
    {
        $enabled = app('settings')->get('promotion.auto.enabled', '0') === '1';
        $monthDay = app('settings')->get('promotion.auto.month_day', ''); // e.g., 06-01
        $lastYearRun = (int) app('settings')->get('promotion.auto.last_year_run', '0');

        if (! $enabled || ! preg_match('/^\d{2}-\d{2}$/', (string) $monthDay)) {
            $this->info('Auto-promotion disabled or not configured.');
            return Command::SUCCESS;
        }

        $today = now();
        $todayMonthDay = $today->format('m-d');
        $currentYear = (int) $today->format('Y');

        if ($todayMonthDay !== $monthDay) {
            $this->info('Today does not match configured month/day.');
            return Command::SUCCESS;
        }

        if ($lastYearRun === $currentYear) {
            $this->info('Auto-promotion already run this year.');
            return Command::SUCCESS;
        }

        $count = $service->promoteAll();
        app('settings')->set('promotion.auto.last_year_run', (string) $currentYear, 'promotion');
        $this->info("Auto-promoted {$count} students.");
        return Command::SUCCESS;
    }
}
