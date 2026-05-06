<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\GlAccount;

class CostCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'cost_center_code',
        'cost_center_name',
        'dimension',
        'cost_center_owner',
        'division',
        'department',
        'cc',
        'general_cc',       
        'gl_accounts',
        'lookup',
        'opex_mapping',
        'created_by',
    ];

    protected $casts = [
        'gl_accounts' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function glAccountRecords()
    {
        $ids = collect($this->gl_accounts ?? [])->pluck('id')->filter()->values()->toArray();
        return GlAccount::whereIn('id', $ids)->get();
    }
}
