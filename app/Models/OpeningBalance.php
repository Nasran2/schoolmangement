<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpeningBalance extends Model
{
    protected $fillable = ['date', 'amount'];
    
    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];
}
