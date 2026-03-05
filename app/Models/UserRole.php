<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRole extends Model
{
    protected $fillable = [
        'user_id', 'sub_department_id', 'role_type', 'level',
        'can_view', 'can_create', 'can_edit', 'can_delete', 'can_approve', 'can_export', 'can_manage',
    ];

    protected $casts = [
        'can_view'    => 'boolean',
        'can_create'  => 'boolean',
        'can_edit'    => 'boolean',
        'can_delete'  => 'boolean',
        'can_approve' => 'boolean',
        'can_export'  => 'boolean',
        'can_manage'  => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subDepartment()
    {
        return $this->belongsTo(SubDepartment::class);
    }

    /**
     * Compute the level string from the current boolean flags.
     */
    public function computeLevel(): string
    {
        if ($this->can_view && $this->can_create && $this->can_edit && $this->can_delete
            && $this->can_approve && $this->can_export && $this->can_manage) {
            return 'Admin';
        }
        if ($this->can_view && $this->can_create && $this->can_edit && $this->can_export) {
            return 'Editor';
        }
        return 'Viewer';
    }

    /**
     * Set the boolean flags from a level string.
     */
    public function applyLevel(string $level): void
    {
        match ($level) {
            'Admin' => $this->fill([
                'can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true,
                'can_approve' => true, 'can_export' => true, 'can_manage' => true,
            ]),
            'Editor' => $this->fill([
                'can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false,
                'can_approve' => false, 'can_export' => true, 'can_manage' => false,
            ]),
            default => $this->fill([
                'can_view' => true, 'can_create' => false, 'can_edit' => false, 'can_delete' => false,
                'can_approve' => false, 'can_export' => false, 'can_manage' => false,
            ]),
        };
        $this->level = $level;
    }
}
