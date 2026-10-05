<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentResult extends Model
{
    public const STATUSES = ['graded', 'absent', 'not_entered'];

    protected $fillable = [
        'assessment_id',
        'learner_id',
        'status',
        'score',
        'entered_by',
        'entered_at',
    ];

    protected function casts(): array
    {
        return ['score' => 'decimal:2', 'entered_at' => 'datetime'];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(Learner::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(AssessmentResultChange::class)->orderByDesc('id');
    }

    public function correctionRequests(): HasMany
    {
        return $this->hasMany(AssessmentCorrectionRequest::class)->orderByDesc('id');
    }
}
