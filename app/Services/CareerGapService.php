<?php

namespace App\Services;

use App\Models\CareerGoal;
use App\Models\EmployeeCompetency;
use App\Models\PositionCompetency;
use App\Models\Training;
use Illuminate\Support\Collection;

class CareerGapService
{
    /** @return Collection<int, array{competency: string, current: ?int, required: int, gap: int, recommendations: string}> */
    public function analyze(CareerGoal $goal): Collection
    {
        if (! $goal->target_position_id || ! $goal->targetPosition) {
            return collect();
        }
        $levels = EmployeeCompetency::where('user_id', $goal->user_id)->pluck('level', 'competency_id');
        $standards = PositionCompetency::with('competency')
            ->where('position_id', $goal->target_position_id)
            ->get();
        $trainings = Training::available()
            ->whereIn('competency_id', $standards->pluck('competency_id'))
            ->get()
            ->groupBy('competency_id');

        return $standards->map(function (PositionCompetency $standard) use ($levels, $trainings): array {
            $current = $levels->has($standard->competency_id)
                ? (int) $levels[$standard->competency_id]
                : null;
            $gap = max(0, $standard->required_level - ($current ?? 0));
            $names = $trainings->get($standard->competency_id, collect())->pluck('name')->join(', ');

            return [
                'competency' => $standard->competency->name,
                'current' => $current,
                'required' => $standard->required_level,
                'gap' => $gap,
                'recommendations' => $gap ? ($names ?: 'Ajukan mentoring') : 'Terpenuhi',
            ];
        })->sortByDesc('gap')->values();
    }

    public function summary(CareerGoal $goal): string
    {
        $analysis = $this->analyze($goal);
        if ($analysis->isEmpty()) {
            return 'Standar kompetensi jabatan belum ditetapkan.';
        }

        $gaps = $analysis->where('gap', '>', 0);

        return $gaps->isEmpty()
            ? 'Semua standar kompetensi terpenuhi.'
            : $gaps->map(function (array $gap): string {
                $current = $gap['current'] ?? 'Belum dinilai';

                return "{$gap['competency']}: {$current}/{$gap['required']} — {$gap['recommendations']}";
            })->join("\n");
    }
}
