<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiquidationFormItem extends Model
{
    use HasFactory;

    protected $fillable = ['liquidation_form_id', 'particulars', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    public function liquidationForm()
    {
        return $this->belongsTo(LiquidationForm::class);
    }
}
