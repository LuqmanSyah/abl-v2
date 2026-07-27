<?php

namespace App\Models;

use App\Exceptions\BusinessRuleException;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSchedule extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $schedule): void {
            if (CarbonImmutable::parse($schedule->check_out_time)
                ->lessThanOrEqualTo(CarbonImmutable::parse($schedule->check_in_time))) {
                throw new BusinessRuleException('Jam pulang harus setelah jam masuk.');
            }
        });
    }

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
