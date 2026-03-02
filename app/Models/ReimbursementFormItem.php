<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReimbursementFormItem extends Model
{
    use HasFactory;

    protected $fillable = ['reimbursement_form_id', 'date', 'particulars', 'cost'];

    protected $casts = ['date' => 'date', 'cost' => 'decimal:2'];

    public function reimbursementForm()
    {
        return $this->belongsTo(ReimbursementForm::class);
    }
}
