<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Learner extends Model
{
    protected $fillable = [
        'student_code',
        'full_name',
        'date_of_birth',
        'cohort',
        'major',
        'class_name',
        'study_status',
        'phone',
        'email',
        'department_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function courseSections(): BelongsToMany
    {
        return $this->belongsToMany(CourseSection::class, 'course_section_learner')
            ->withPivot(['effective_from', 'effective_until', 'is_new'])
            ->withTimestamps();
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class);
    }

    public function assessmentResults(): HasMany
    {
        return $this->hasMany(AssessmentResult::class);
    }

    public function dataWarnings(): array
    {
        $warnings = [];
        $missingFields = [
            'date_of_birth' => 'ngày sinh',
            'cohort' => 'khóa',
            'major' => 'ngành / chương trình',
            'class_name' => 'lớp quản lý',
            'phone' => 'số điện thoại',
            'email' => 'email',
            'department_id' => 'khoa / đơn vị',
        ];

        foreach ($missingFields as $field => $label) {
            if (blank($this->{$field})) {
                $warnings[] = "Thiếu {$label}";
            }
        }

        $hasActiveEnrollment = $this->courseSections->contains(function (CourseSection $section): bool {
            $effectiveFrom = $section->pivot->effective_from;
            $effectiveUntil = $section->pivot->effective_until;

            return (! $effectiveFrom || (string) $effectiveFrom <= now()->toDateString())
                && (! $effectiveUntil || (string) $effectiveUntil >= now()->toDateString());
        });

        if (! $hasActiveEnrollment) {
            $warnings[] = 'Chưa đăng ký lớp học phần';
        }

        if ($this->date_of_birth && ($this->date_of_birth->age < 15 || $this->date_of_birth->age > 100)) {
            $warnings[] = 'Ngày sinh cần được kiểm tra';
        }

        return $warnings;
    }
}
