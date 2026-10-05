<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [
        'course_section_id',
        'session_number',
        'session_date',
        'start_time',
        'end_time',
        'room',
        'status',
        'opened_by',
        'opened_at',
        'confirmed_by',
        'confirmed_at',
        'editable_until',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'opened_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'editable_until' => 'datetime',
        ];
    }

    public function courseSection(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class)->orderBy('id');
    }
}
