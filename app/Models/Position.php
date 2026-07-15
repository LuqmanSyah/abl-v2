<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use HasFactory;

    protected $fillable = ['unit_id', 'name', 'level'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function competencyStandards(): HasMany
    {
        return $this->hasMany(PositionCompetency::class);
    }

    public function careerGoals(): HasMany
    {
        return $this->hasMany(CareerGoal::class, 'target_position_id');
    }
}
