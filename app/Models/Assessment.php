<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $fillable = [
        'course_section_id',
        'title',
        'kind',
        'max_score',
        'weight',
        'edit_until',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'max_score' => 'decimal:2',
            'weight' => 'decimal:2',
            'edit_until' => 'date',
        ];
    }

    public function courseSection(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(AssessmentResult::class)->orderBy('learner_id');
    }
}
