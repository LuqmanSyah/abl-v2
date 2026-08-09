<?php

namespace App\Models;

use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class ReviewPeriod extends Model
{
    use HasFactory;

    protected $attributes = ['is_active' => true];

    protected $fillable = [
        'name', 'starts_at', 'ends_at', 'kpi_weight', 'discipline_weight',
        'manager_weight', 'review_360_weight', 'base_bonus', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'kpi_weight' => 'integer',
            'discipline_weight' => 'integer',
            'manager_weight' => 'integer',
            'review_360_weight' => 'integer',
            'base_bonus' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $period): void {
            if ($period->exists && $period->isDirty($period->getFillable()) && $period->hasPublishedMeritResults()) {
                throw new BusinessRuleException('Periode dengan hasil merit terpublikasi tidak dapat diubah.');
            }

            if ($period->exists
                && $period->isDirty($period->getFillable())
                && Carbon::parse($period->getRawOriginal('ends_at'))->endOfDay()->isPast()) {
                throw new BusinessRuleException('Periode yang telah selesai tidak dapat diubah.');
            }

            $total = $period->kpi_weight + $period->discipline_weight + $period->manager_weight + $period->review_360_weight;
            if (abs($total - 100) > 0.01) {
                throw new BusinessRuleException('Total bobot merit wajib 100%.');
            }
            if ($period->ends_at->isBefore($period->starts_at)) {
                throw new BusinessRuleException('Tanggal selesai harus setelah tanggal mulai.');
            }
            if ((float) $period->base_bonus < 0) {
                throw new BusinessRuleException('Dasar simulasi bonus tidak boleh negatif.');
            }

            if ($period->is_active && self::query()
                ->where('is_active', true)
                ->when($period->exists, fn ($query) => $query->whereKeyNot($period->id))
                ->whereDate('starts_at', '<=', $period->ends_at)
                ->whereDate('ends_at', '>=', $period->starts_at)
                ->exists()) {
                throw new BusinessRuleException('Periode aktif tidak boleh memiliki rentang tanggal yang tumpang tindih.');
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

    public function hasStarted(): bool
    {
        return $this->starts_at->copy()->startOfDay()->lte(now());
    }

    public function hasEnded(): bool
    {
        return $this->ends_at->copy()->endOfDay()->isPast();
    }

    public function acceptsReviews(): bool
    {
        return $this->is_active && $this->hasStarted() && ! $this->hasEnded() && ! $this->hasPublishedMeritResults();
    }
}
