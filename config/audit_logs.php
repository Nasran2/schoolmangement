<?php

return [
    // Number of days to keep audit logs in the database.
    // Older records are deleted by the scheduled audit-logs:prune command.
    'retention_days' => (int) ($_ENV['AUDIT_LOG_RETENTION_DAYS'] ?? ($_SERVER['AUDIT_LOG_RETENTION_DAYS'] ?? 10)),
];
