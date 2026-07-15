<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'starts_at', 'ends_at', 'kpi_weight', 'discipline_weight',
        'manager_weight', 'review_360_weight', 'base_bonus', 'is_active',
    ];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'ends_at' => 'date', 'base_bonus' => 'decimal:2', 'is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $period): void {
            if ($period->exists && $period->isDirty($period->getFillable()) && $period->hasPublishedMeritResults()) {
                throw new DomainException('Periode dengan hasil merit terpublikasi tidak dapat diubah.');
            }

            $total = $period->kpi_weight + $period->discipline_weight + $period->manager_weight + $period->review_360_weight;
            if ($total !== 100) {
                throw new DomainException('Total bobot merit wajib 100%.');
            }
            if ($period->ends_at->isBefore($period->starts_at)) {
                throw new DomainException('Tanggal selesai harus setelah tanggal mulai.');
            }
        });
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(KpiIndicator::class);
    }

    public function meritResults(): HasMany
    {
        return $this->hasMany(MeritResult::class);
    }

    public function hasPublishedMeritResults(): bool
    {
        return $this->meritResults()->whereNotNull('published_at')->exists();
    }
}
