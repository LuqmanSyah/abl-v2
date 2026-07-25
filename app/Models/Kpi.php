<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kpi extends Model
{
    protected $fillable = [
        'name',
        'category',
        'weight',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
    ];

    public function reviewKpiDetails(): HasMany
    {
        return $this->hasMany(ReviewKpiDetail::class);
    }
}
