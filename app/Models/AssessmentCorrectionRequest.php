<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentCorrectionRequest extends Model
{
    protected $fillable = [
        'assessment_result_id',
        'learner_id',
        'requested_by',
        'requester_type',
        'original_status',
        'original_score',
        'requested_status',
        'requested_score',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'original_score' => 'decimal:2',
            'requested_score' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function assessmentResult(): BelongsTo
    {
        return $this->belongsTo(AssessmentResult::class);
    }

    public function learner(): BelongsTo
    {
        return $this->belongsTo(Learner::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
