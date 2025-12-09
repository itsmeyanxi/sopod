<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlySale extends Model
{
    use HasFactory;

    protected $fillable = [
        'month',
        'quantity',
        'php_amount'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'php_amount' => 'decimal:2'
    ];
}