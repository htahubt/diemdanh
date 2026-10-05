<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lecturer extends Model
{
    protected $fillable = [
        'lecturer_code',
        'name',
        'department_id',
        'academic_rank_id',
        'academic_degree_id',
        'date_of_birth',
        'gender',
        'academic_rank',
        'academic_title',
        'specialization',
        'department',
        'position',
        'email',
        'phone',
        'national_id',
        'national_id_hash',
        'id_issued_at',
        'id_issued_place',
        'permanent_address',
        'contact_address',
        'bank_account_number',
        'bank_name',
        'bank_branch',
        'bank_account_name',
        'profile_status',
        'review_note',
        'reviewed_by',
        'submitted_at',
        'approved_at',
        'identity_verified_at',
        'bank_verified_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'id_issued_at' => 'date',
            'national_id' => 'encrypted',
            'bank_account_number' => 'encrypted',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'identity_verified_at' => 'datetime',
            'bank_verified_at' => 'datetime',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(LecturerDocument::class);
    }

    public function departmentUnit(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function academicRank(): BelongsTo
    {
        return $this->belongsTo(AcademicOption::class, 'academic_rank_id');
    }

    public function academicDegree(): BelongsTo
    {
        return $this->belongsTo(AcademicOption::class, 'academic_degree_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(LecturerProfileHistory::class)->orderByDesc('id');
    }

    public function courseSections(): HasMany
    {
        return $this->hasMany(CourseSection::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }
}
