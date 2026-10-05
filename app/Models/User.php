<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'learner_id',
        'lecturer_id',
        'department_id',
        'can_view_sensitive_profiles',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'can_view_sensitive_profiles' => 'boolean',
        ];
    }

    public function canViewSensitiveProfiles(): bool
    {
        return in_array($this->role, ['admin', 'training_office'], true) || $this->can_view_sensitive_profiles;
    }

    public function canManageLearners(): bool
    {
        return in_array($this->role, ['admin', 'training_office'], true);
    }

    public function canManageTraining(): bool
    {
        return $this->canManageLearners();
    }

    public function canManageStudentCases(): bool
    {
        return in_array($this->role, ['admin', 'training_office', 'student_affairs', 'unit_leader'], true);
    }

    public function canAccessTrainingDepartment(?int $departmentId): bool
    {
        return in_array($this->role, ['admin', 'training_office'], true)
            || (in_array($this->role, ['student_affairs', 'unit_leader'], true)
                && $departmentId !== null
                && (int) $this->department_id === $departmentId);
    }

    public function canReadTrainingData(): bool
    {
        return in_array($this->role, ['admin', 'training_office'], true)
            || (in_array($this->role, ['student_affairs', 'unit_leader'], true) && $this->department_id !== null);
    }

    public function learner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Learner::class);
    }

    public function lecturerProfile(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Lecturer::class, 'lecturer_id');
    }

    public function lecturerProfileId(): ?int
    {
        $lecturerId = $this->lecturer_id ?? Lecturer::where('email', $this->email)->value('id');

        return $lecturerId === null ? null : (int) $lecturerId;
    }

    public function isAssignedToLecturer(?int $lecturerId): bool
    {
        return $this->role === 'lecturer'
            && $lecturerId !== null
            && $this->lecturerProfileId() === $lecturerId;
    }

    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function canViewLecturerProfile(Lecturer $lecturer): bool
    {
        return $this->canViewSensitiveProfiles()
            || ($this->role === 'lecturer' && (int) $this->lecturerProfileId() === (int) $lecturer->id)
            || $this->email === $lecturer->email;
    }
}
