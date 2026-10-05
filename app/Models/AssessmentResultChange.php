<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentResultChange extends Model
{
    protected $fillable = [
        'assessment_result_id',
        'actor_id',
        'before_status',
        'before_score',
        'after_status',
        'after_score',
        'reason',
    ];

    protected function casts(): array
    {
        return ['before_score' => 'decimal:2', 'after_score' => 'decimal:2'];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(AssessmentResult::class, 'assessment_result_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
