<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceAlertSetting extends Model
{
    protected $fillable = ['rule_key', 'label', 'threshold', 'unit', 'is_enabled', 'updated_by'];

    protected function casts(): array
    {
        return ['threshold' => 'decimal:2', 'is_enabled' => 'boolean'];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
