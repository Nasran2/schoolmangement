<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo(null, 'auditable_type', 'auditable_id');
    }

    public function getFormattedDescriptionAttribute(): ?string
    {
        $action = (string) ($this->action ?? '');
        $meta = is_array($this->metadata) ? $this->metadata : [];

        try {
            if ($action === 'rbac.role.permissions.update') {
                $role = (string) ($meta['role'] ?? 'Role');
                $added = isset($meta['added']) && is_array($meta['added']) ? $meta['added'] : [];
                $removed = isset($meta['removed']) && is_array($meta['removed']) ? $meta['removed'] : [];
                $addedTxt = count($added) ? implode(', ', $added) : 'none';
                $removedTxt = count($removed) ? implode(', ', $removed) : 'none';
                return "Permissions for '$role' updated: added [{$addedTxt}], removed [{$removedTxt}].";
            }

            if (in_array($action, ['teacher.salary_updated', 'teachers.salary.update', 'teacher_salary_updated'], true)) {
                $before = $meta['before']['amount'] ?? null;
                $after = $meta['after']['amount'] ?? null;
                if ($before !== null && $after !== null) {
                    return 'Teacher monthly salary updated: Rs '.number_format((float) $before, 0).' → Rs '.number_format((float) $after, 0).'.';
                }
                return $this->description ?: 'Teacher salary updated.';
            }

            // Revenue: create/update/delete
            if (in_array($action, ['revenue.create', 'revenue.update', 'revenue.delete'], true)) {
                $bill = $meta['bill_no'] ?? null;
                $amt = $meta['amount'] ?? null;
                $label = 'Revenue '.str_replace('revenue.', '', $action);
                $parts = [];
                if ($bill) $parts[] = 'Bill '.$bill;
                if ($amt !== null) $parts[] = 'Rs '.number_format((float) $amt, 2);
                return ($parts ? implode(', ', $parts).'. ' : '') . ($this->description ?: $label);
            }

            // Expense: create/update/delete
            if (in_array($action, ['expense.create', 'expense.update', 'expense.delete'], true)) {
                $date = $meta['expense_date'] ?? null;
                $amt = $meta['amount'] ?? null;
                $label = 'Expense '.str_replace('expense.', '', $action);
                $parts = [];
                if ($date) $parts[] = 'Date '.(string) $date;
                if ($amt !== null) $parts[] = 'Rs '.number_format((float) $amt, 2);
                return ($parts ? implode(', ', $parts).'. ' : '') . ($this->description ?: $label);
            }

            // Salary payment updated (detailed in metadata)
            if ($action === 'salary_payment.updated') {
                $after = $meta['after'] ?? [];
                $amt = $after['amount'] ?? null;
                $month = $after['payment_month'] ?? null;
                $txt = 'Salary payment updated';
                if ($amt !== null) {
                    $txt .= ': Rs '.number_format((float) $amt, 2);
                }
                if ($month) {
                    $txt .= ' for '.$month;
                }
                return $txt.'.';
            }

            // Fallback to existing description
            return $this->description;
        } catch (\Throwable $e) {
            return $this->description;
        }
    }

    public function getFormattedMetadataAttribute(): ?string
    {
        $action = (string) ($this->action ?? '');
        $meta = is_array($this->metadata) ? $this->metadata : [];

        try {
            if (in_array($action, ['teacher.salary_updated', 'teachers.salary.update', 'teacher_salary_updated'], true)) {
                $beforeComps = $meta['before']['components'] ?? [];
                $afterComps = $meta['after']['components'] ?? [];
                if (is_array($beforeComps) && is_array($afterComps)) {
                    $lines = [];
                    foreach ($afterComps as $ac) {
                        $type = $ac['type'] ?? '';
                        $afterAmt = $ac['amount'] ?? null;
                        $beforeAmt = null;
                        foreach ($beforeComps as $bc) {
                            if (($bc['type'] ?? '') === $type) {
                                $beforeAmt = $bc['amount'] ?? null;
                                break;
                            }
                        }
                        if ($type !== '' && $afterAmt !== null) {
                            $lines[] = $type.' Rs '.number_format((float) ($beforeAmt ?? 0), 0).' → Rs '.number_format((float) $afterAmt, 0);
                        }
                    }
                    if (! empty($lines)) {
                        return 'Components: '.implode('; ', $lines).'.';
                    }
                }
            }

            // Generic summary: scalar metadata pairs
            if (! empty($meta)) {
                $pairs = [];
                foreach ($meta as $k => $v) {
                    if (is_scalar($v)) {
                        $pairs[] = $k.': '.$v;
                    }
                    // Special cases when metadata has nested arrays
                    if ($k === 'after' && is_array($v) && $action === 'salary_payment.updated') {
                        $amount = $v['amount'] ?? null;
                        $ded = $v['total_deductions'] ?? null;
                        $method = $v['payment_method'] ?? null;
                        $pieces = [];
                        if ($amount !== null) $pieces[] = 'Amount Rs '.number_format((float)$amount, 2);
                        if ($ded !== null) $pieces[] = 'Deductions Rs '.number_format((float)$ded, 2);
                        if ($method) $pieces[] = 'Method '.$method;
                        if (! empty($pieces)) {
                            $pairs[] = implode(' · ', $pieces);
                        }
                    }
                }
                if (! empty($pairs)) {
                    return implode(' · ', $pairs);
                }
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
