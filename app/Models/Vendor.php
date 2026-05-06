<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        'vendor_code', 'vendor_name', 'category', 'group', 'gl_account', 'status',
        'company', 'ee_id', 'last_name', 'first_name', 'middle_name',
        'position', 'department', 'location', 'office_address', 'date_hired',
    ];

    protected $casts = ['date_hired' => 'date'];
}
