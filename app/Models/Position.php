<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $fillable = [
        'department_id',
        'title',
        'level',
    ];

    protected $casts = [
        'level' => 'integer',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function positionSkills(): HasMany
    {
        return $this->hasMany(PositionSkill::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
