<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerTransferReturn extends Model
{
    protected $fillable = ['submission_id', 'returned_at', 'amount', 'notes', 'created_by'];

    protected $casts = ['returned_at' => 'date', 'amount' => 'decimal:2'];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(OwnerTransfer::class, 'owner_transfer_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('transfer', fn ($query) => $query->active());
    }

    public function getEffectiveAmountAttribute(): float
    {
        return $this->transfer->deleted_at ? 0.0 : (float) $this->amount;
    }

    public function getDescriptionAttribute(): string
    {
        return 'Return amount from cash transfer to owner account — OT-'.$this->owner_transfer_id.' — '.$this->transfer->destination.($this->notes ? ' — '.$this->notes : '');
    }
}
