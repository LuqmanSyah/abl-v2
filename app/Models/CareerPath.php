<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerPath extends Model
{
    protected $fillable = [
        'current_position_id',
        'next_position_id',
        'min_experience_months',
        'min_merit_grade',
    ];

    protected $casts = [
        'min_experience_months' => 'integer',
    ];

    public function currentPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'current_position_id');
    }

    public function nextPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'next_position_id');
    }
}
