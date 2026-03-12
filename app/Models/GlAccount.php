<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlAccount extends Model
{
    protected $table = 'gl_accounts';

    protected $fillable = [
        'account_code',
        'account_name',
        'fs_line_item',
        'fs_notes',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
