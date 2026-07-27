<?php

namespace App\Models;

use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Kpi extends Model
{
    public function save(array $options = []): bool
    {
        return DB::transaction(function () use ($options): bool {
            self::lockAll();

            return parent::save($options);
        });
    }

    public function delete()
    {
        return DB::transaction(function () {
            self::lockAll();

            return parent::delete();
        });
    }

    protected static function booted(): void
    {
        static::saving(function (self $kpi): void {
            $others = self::query()
                ->when($kpi->exists, fn ($query) => $query->whereKeyNot($kpi))
                ->get(['weight'])
                ->sum('weight');

            $current = (float) $others + ($kpi->exists ? (float) $kpi->getRawOriginal('weight') : 0);
            self::guardChange($current, (float) $others + (float) $kpi->weight);
        });

        static::deleting(function (self $kpi): void {
            $remaining = self::query()
                ->whereKeyNot($kpi)
                ->get(['weight'])
                ->sum('weight');

            self::guardChange((float) $remaining + (float) $kpi->weight, (float) $remaining);
        });
    }

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

    /** @param array<int|string, int|float|string> $weights */
    public static function rebalance(array $weights): void
    {
        DB::transaction(function () use ($weights): void {
            self::lockAll();
            $kpis = self::query()->orderBy('id')->get(['id', 'weight']);
            $normalized = [];

            foreach ($weights as $id => $weight) {
                if (! is_numeric($weight)
                    || ! is_finite((float) $weight)
                    || (float) $weight < 0
                    || (float) $weight > 100) {
                    throw new BusinessRuleException('Bobot KPI harus berada di antara 0 dan 100.');
                }

                $normalized[(int) $id] = round((float) $weight, 2);
            }

            if ($kpis->count() !== count($normalized)
                || $kpis->contains(fn (self $kpi): bool => ! array_key_exists($kpi->id, $normalized))) {
                throw new BusinessRuleException('Semua KPI wajib disertakan saat rebalance.');
            }

            if (round(array_sum($normalized), 2) !== 100.0) {
                throw new BusinessRuleException('Total bobot master KPI harus tetap 100.');
            }

            foreach ($kpis as $kpi) {
                $kpi->forceFill(['weight' => $normalized[$kpi->id]])->saveQuietly();
            }
        });
    }

    private static function lockAll(): void
    {
        self::query()
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id']);
    }

    private static function guardChange(float $current, float $next): void
    {
        $current = round($current, 2);
        $next = round($next, 2);

        if (($current === 100.0 && $next !== 100.0)
            || ($current !== 100.0 && abs(100 - $next) >= abs(100 - $current))) {
            throw new BusinessRuleException('Total bobot master KPI harus tetap 100.');
        }
    }
}
