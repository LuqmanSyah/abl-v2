<?php

namespace App\Models;

use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewPeriod extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $period): void {
            if ($period->exists && $period->getRawOriginal('published_at') && $period->isDirty()) {
                throw new BusinessRuleException('Periode yang telah dipublikasikan tidak dapat diubah.');
            }

            if ($period->ends_at->isBefore($period->starts_at)) {
                throw new BusinessRuleException('Tanggal selesai harus setelah tanggal mulai.');
            }
        });
    }

    public function kpis(): HasMany
    {
        return $this->hasMany(EmployeeKpi::class);
    }

    public function meritResults(): HasMany
    {
        return $this->hasMany(MeritResult::class);
    }
}
