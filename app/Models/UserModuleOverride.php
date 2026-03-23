<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserModuleOverride extends Model
{
    protected $fillable = ['user_id', 'module', 'allowed'];

    protected $casts = [
        'allowed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
