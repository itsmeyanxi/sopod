<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderChange extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'user_id',
        'field_changed',
        'old_value',
        'new_value',
        'change_type',
        'reason',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifications()
    {
        return $this->hasMany(ChangeNotification::class);
    }

    // Helper to format the change for display
    public function getFormattedChangeAttribute()
    {
        $field = ucwords(str_replace('_', ' ', $this->field_changed));
        return "{$field}: {$this->old_value} → {$this->new_value}";
    }
}