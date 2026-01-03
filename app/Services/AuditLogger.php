<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function log(
        string $action,
        ?object $auditable = null,
        ?string $description = null,
        array $metadata = []
    ): void {
        $user = Auth::user();

        AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable && property_exists($auditable, 'id') ? $auditable->id : null,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => (string) request()->userAgent(),
        ]);
    }
}
