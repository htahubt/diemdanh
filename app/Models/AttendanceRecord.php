<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRecord extends Model
{
    public const STATUSES = ['present', 'late', 'excused', 'unexcused', 'other', 'unconfirmed'];

    protected $fillable = [
        'attendance_session_id',
        'learner_id',
        'status',
        'recorded_by',
        'marked_at',
    ];

    protected function casts(): array
    {
        return ['marked_at' => 'datetime'];
    }

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(Learner::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(AttendanceRecordChange::class)->orderByDesc('id');
    }
}
