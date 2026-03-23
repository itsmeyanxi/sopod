<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BomMaterial extends Model
{
    protected $fillable = [
        'item_code',
        'item_description',
        'uom',
        'category',
        'default_cost',
        'notes',
    ];
}
