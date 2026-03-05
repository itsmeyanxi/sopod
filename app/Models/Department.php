<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name', 'color'];

    public function subDepartments()
    {
        return $this->hasMany(SubDepartment::class);
    }
}
