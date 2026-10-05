<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RosterChangeLog extends Model
{
    protected $fillable = [
        'course_section_id',
        'section_code',
        'learner_id',
        'learner_code',
        'action',
        'effective_from',
        'effective_until',
        'before_values',
        'after_values',
        'actor_id',
        'approved_by',
        'approved_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
            'before_values' => 'array',
            'after_values' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    public function courseSection(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class);
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(Learner::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
