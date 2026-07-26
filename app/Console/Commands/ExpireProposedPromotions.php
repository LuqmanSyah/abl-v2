<?php

namespace App\Console\Commands;

use App\Enums\PromotionStatus;
use App\Models\Promotion;
use Illuminate\Console\Command;

class ExpireProposedPromotions extends Command
{
    protected $signature = 'career:expire-promotions';

    protected $description = 'Expire promotion proposals older than 30 days';

    public function handle(): int
    {
        $expired = Promotion::query()
            ->where('status', PromotionStatus::Proposed)
            ->where('created_at', '<', now()->subDays(30))
            ->update(['status' => PromotionStatus::Expired]);

        $this->info("{$expired} promotion proposal(s) expired.");

        return self::SUCCESS;
    }
}
