<?php

namespace App\Console\Commands;

use App\Enums\PromotionStatus;
use App\Models\Promotion;
use Illuminate\Console\Command;

class ApplyEffectivePromotions extends Command
{
    protected $signature = 'career:apply-promotions';

    protected $description = 'Apply approved promotions on their effective date';

    public function handle(): int
    {
        $applied = 0;

        Promotion::query()
            ->where('status', PromotionStatus::ApprovedByDirector)
            ->whereNull('applied_at')
            ->whereDate('effective_date', '<=', today())
            ->eachById(function (Promotion $promotion) use (&$applied): void {
                $applied += (int) $promotion->applyIfDue();
            });

        $this->info("{$applied} promotion(s) applied.");

        return self::SUCCESS;
    }
}
