<?php

namespace App\Notifications;

use App\Models\Promotion;

class PromotionProposed extends WorkflowNotification
{
    public function __construct(public Promotion $promotion) {}

    protected function payload(): array
    {
        return [
            'title' => 'Usulan Promosi Baru',
            'body' => "{$this->promotion->user->name} diusulkan ke posisi {$this->promotion->toPosition->title}.",
            'url' => url('/admin/promotions'),
            'icon' => 'heroicon-o-rocket-launch',
        ];
    }
}
