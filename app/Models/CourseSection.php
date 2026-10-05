<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseSection extends Model
{
    protected $fillable = [
        'course_id',
        'section_code',
        'semester',
        'academic_year',
        'department_id',
        'training_format',
        'weekday',
        'start_time',
        'end_time',
        'room',
        'expected_sessions',
        'starts_on',
        'ends_on',
        'lecturer_id',
        'capacity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'semester' => 'integer',
            'capacity' => 'integer',
            'weekday' => 'integer',
            'expected_sessions' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lecturer(): BelongsTo
    {
        return $this->belongsTo(Lecturer::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function learners(): BelongsToMany
    {
        return $this->belongsToMany(Learner::class, 'course_section_learner')
            ->withPivot(['effective_from', 'effective_until', 'is_new'])
            ->withTimestamps();
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(AttendanceSession::class)->orderBy('session_number');
    }

    public function rosterChangeLogs(): HasMany
    {
        return $this->hasMany(RosterChangeLog::class)->orderByDesc('id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class)->orderBy('id');
    }
}
