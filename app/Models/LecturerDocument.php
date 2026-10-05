<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LecturerDocument extends Model
{
    protected $fillable = [
        'document_type',
        'original_name',
        'storage_path',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }
}