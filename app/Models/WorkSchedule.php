<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSchedule extends Model
{
    protected $fillable = [
        'name',
        'check_in_time',
        'check_out_time',
        'late_tolerance_minutes',
        'alfa_cutoff_minutes',
    ];

    protected $casts = [
        'late_tolerance_minutes' => 'integer',
        'alfa_cutoff_minutes' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
