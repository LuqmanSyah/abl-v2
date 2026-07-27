<?php

namespace App\Notifications;

use App\Models\Promotion;

class PromotionApproved extends WorkflowNotification
{
    public function __construct(public Promotion $promotion) {}

    protected function payload(): array
    {
        return [
            'title' => 'Promosi Disetujui',
            'body' => "Promosi {$this->promotion->user->name} ke posisi {$this->promotion->toPosition->title} telah disetujui.",
            'url' => url('/app'),
            'icon' => 'heroicon-o-trophy',
        ];
    }
}
