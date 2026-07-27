<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->deduplicate('user_skills', ['user_id', 'skill_id'], 'MAX');
        $this->deduplicateReviewKpiDetails();

        Schema::table('user_skills', function (Blueprint $table) {
            $table->unique(['user_id', 'skill_id'], 'user_skills_user_skill_unique');
        });

        Schema::table('review_kpi_details', function (Blueprint $table) {
            $table->unique(
                ['performance_review_id', 'kpi_id'],
                'review_kpi_details_review_kpi_unique',
            );
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->timestamp('applied_at')->nullable()->after('effective_date');
            $table->boolean('active_lifecycle')->nullable()->after('applied_at');
        });

        $this->expireDuplicateActivePromotions();

        DB::table('promotions')
            ->whereIn('status', ['proposed', 'approved_by_hr', 'approved_by_director'])
            ->update(['active_lifecycle' => true]);

        Schema::table('promotions', function (Blueprint $table) {
            $table->unique(
                ['user_id', 'to_position_id', 'active_lifecycle'],
                'promotions_active_lifecycle_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropUnique('promotions_active_lifecycle_unique');
            $table->dropColumn(['applied_at', 'active_lifecycle']);
        });

        Schema::table('review_kpi_details', function (Blueprint $table) {
            $table->dropUnique('review_kpi_details_review_kpi_unique');
        });

        Schema::table('user_skills', function (Blueprint $table) {
            $table->dropUnique('user_skills_user_skill_unique');
        });
    }

    /** @param list<string> $columns */
    private function deduplicate(string $table, array $columns, string $aggregate): void
    {
        $duplicates = DB::table($table)
            ->select($columns)
            ->selectRaw("{$aggregate}(id) AS keep_id")
            ->groupBy($columns)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $query = DB::table($table);

            foreach ($columns as $column) {
                $query->where($column, $duplicate->{$column});
            }

            $query->where('id', '<>', $duplicate->keep_id)->delete();
        }
    }

    private function deduplicateReviewKpiDetails(): void
    {
        $duplicates = DB::table('review_kpi_details')
            ->select(['performance_review_id', 'kpi_id'])
            ->groupBy(['performance_review_id', 'kpi_id'])
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $details = DB::table('review_kpi_details')
                ->where('performance_review_id', $duplicate->performance_review_id)
                ->where('kpi_id', $duplicate->kpi_id)
                ->orderBy('id')
                ->get([
                    'id',
                    'self_score',
                    'self_notes',
                    'manager_score',
                    'manager_notes',
                    'weight',
                ]);
            $canonical = $details->first();
            $merged = [
                'self_score' => $canonical->self_score,
                'self_notes' => $canonical->self_notes,
                'manager_score' => $canonical->manager_score,
                'manager_notes' => $canonical->manager_notes,
            ];

            // Preserve snapshot identity/weight; newest non-null assessment value wins per field.
            foreach ($details as $detail) {
                foreach (array_keys($merged) as $column) {
                    if ($detail->{$column} !== null) {
                        $merged[$column] = $detail->{$column};
                    }
                }
            }

            $merged['subtotal_score'] = $merged['manager_score'] === null
                ? null
                : round((float) $merged['manager_score'] * (float) $canonical->weight / 100, 2);
            $merged['updated_at'] = now();

            DB::table('review_kpi_details')
                ->where('id', $canonical->id)
                ->update($merged);
            DB::table('review_kpi_details')
                ->where('performance_review_id', $duplicate->performance_review_id)
                ->where('kpi_id', $duplicate->kpi_id)
                ->where('id', '<>', $canonical->id)
                ->delete();
        }
    }

    private function expireDuplicateActivePromotions(): void
    {
        $activeStatuses = ['proposed', 'approved_by_hr', 'approved_by_director'];
        $rank = ['proposed' => 1, 'approved_by_hr' => 2, 'approved_by_director' => 3];
        $duplicates = DB::table('promotions')
            ->whereIn('status', $activeStatuses)
            ->select(['user_id', 'to_position_id'])
            ->groupBy(['user_id', 'to_position_id'])
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $promotions = DB::table('promotions')
                ->where('user_id', $duplicate->user_id)
                ->where('to_position_id', $duplicate->to_position_id)
                ->whereIn('status', $activeStatuses)
                ->orderBy('id')
                ->get(['id', 'status']);
            $canonical = $promotions->reduce(
                fn ($keep, $promotion) => ! $keep
                    || $rank[$promotion->status] > $rank[$keep->status]
                    || ($rank[$promotion->status] === $rank[$keep->status]
                        && $promotion->id > $keep->id)
                        ? $promotion
                        : $keep,
            );

            DB::table('promotions')
                ->whereIn(
                    'id',
                    $promotions
                        ->reject(fn ($promotion): bool => $promotion->id === $canonical->id)
                        ->pluck('id'),
                )
                ->update([
                    'status' => 'expired',
                    'active_lifecycle' => null,
                    'updated_at' => now(),
                ]);
        }
    }
};
