<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceAlertHistory extends Model
{
    protected $fillable = ['attendance_alert_id', 'actor_id', 'before_status', 'after_status', 'action_note'];

    public function alert(): BelongsTo
    {
        return $this->belongsTo(AttendanceAlert::class, 'attendance_alert_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
