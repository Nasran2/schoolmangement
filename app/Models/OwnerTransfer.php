<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerTransfer extends Model
{
    public const DESTINATION = 'Owner bank account';

    protected $fillable = ['submission_id', 'transferred_at', 'amount', 'reference', 'notes', 'created_by'];

    protected $casts = [
        'transferred_at' => 'date',
        'amount' => 'decimal:2',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
