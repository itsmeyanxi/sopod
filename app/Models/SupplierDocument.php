<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'document_name',
        'original_filename',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_by',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage()
    {
        return in_array($this->file_type, ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp']);
    }

    public function isPdf()
    {
        return $this->file_type === 'application/pdf';
    }

    public function getFileSizeFormatted()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        return number_format($bytes / 1024, 2) . ' KB';
    }
}
