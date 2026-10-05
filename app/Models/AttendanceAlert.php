<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceAlert extends Model
{
    protected $fillable = [
        'fingerprint', 'alert_type', 'severity', 'title', 'description',
        'learner_id', 'learner_code', 'course_section_id', 'section_code',
        'attendance_session_id', 'details', 'status', 'first_detected_at',
        'last_detected_at', 'assigned_to', 'resolved_by', 'resolved_at', 'resolution_note',
    ];

    protected function casts(): array
    {
        return ['details' => 'array', 'first_detected_at' => 'datetime', 'last_detected_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(Learner::class);
    }

    public function courseSection(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class);
    }

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(AttendanceAlertHistory::class)->orderByDesc('id');
    }
}
