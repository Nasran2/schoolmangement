<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('school:backup')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground();

// Auto-confirm (pass) cheques after N days from cheque date (default: 14)
Schedule::command('cheques:auto-pass')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground();

// Prune audit logs older than N days (default: 10)
Schedule::command('audit-logs:prune --days=' . (int) config('audit_logs.retention_days', 10))
    ->dailyAt('03:30')
    ->withoutOverlapping()
    ->runInBackground();
