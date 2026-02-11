<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class PruneAuditLogs extends Command
{
    protected $signature = 'audit-logs:prune {--days= : Delete audit logs older than N days}';

    protected $description = 'Delete old audit logs to reduce database size.';

    public function handle(): int
    {
        $days = $this->option('days');
        $days = is_numeric($days) ? (int) $days : (int) config('audit_logs.retention_days', 10);

        if ($days < 0) {
            $this->error('Days must be 0 or greater.');
            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $deleted = AuditLog::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} audit log(s) older than {$days} day(s) (before {$cutoff->toDateTimeString()}).");

        return self::SUCCESS;
    }
}
