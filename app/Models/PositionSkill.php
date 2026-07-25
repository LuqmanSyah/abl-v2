<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PositionSkill extends Model
{
    protected $fillable = [
        'position_id',
        'skill_id',
        'min_required_level',
    ];

    protected $casts = [
        'min_required_level' => 'integer',
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
