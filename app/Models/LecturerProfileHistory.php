<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LecturerProfileHistory extends Model
{
    protected $fillable = [
        'actor_id',
        'action',
        'changes',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'encrypted:array',
        ];
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }
}