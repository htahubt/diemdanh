<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicOption extends Model
{
    protected $fillable = [
        'type',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function rankLecturers(): HasMany
    {
        return $this->hasMany(Lecturer::class, 'academic_rank_id');
    }

    public function degreeLecturers(): HasMany
    {
        return $this->hasMany(Lecturer::class, 'academic_degree_id');
    }
}