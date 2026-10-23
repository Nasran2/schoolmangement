<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OwnerTransfer extends Model
{
    public const DESTINATION = 'Owner bank account';

    protected $fillable = ['submission_id', 'transferred_at', 'amount', 'destination', 'reference', 'notes', 'created_by'];

    protected $casts = [
        'transferred_at' => 'date',
        'amount' => 'decimal:2',
        'deleted_at' => 'datetime',
        'version' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OwnerTransferReturn::class);
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    public function getDestinationAttribute(?string $value): string
    {
        return $value ?: self::DESTINATION;
    }

    public function getEffectiveAmountAttribute(): float
    {
        return $this->deleted_at ? 0.0 : (float) $this->amount;
    }

    public function getReturnedAmountAttribute(): float
    {
        return round((float) ($this->returns_sum_amount ?? $this->returns->sum('amount')), 2);
    }

    public function getNetAmountAttribute(): float
    {
        return $this->deleted_at ? 0.0 : round((float) $this->amount - $this->returned_amount, 2);
    }
}
