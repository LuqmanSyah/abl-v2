<?php

namespace App\Notifications;

use App\Models\Promotion;

class PromotionAwaitingDirectorApproval extends WorkflowNotification
{
    public function __construct(public Promotion $promotion) {}

    protected function payload(): array
    {
        return [
            'title' => 'Promosi Menunggu Persetujuan',
            'body' => "Promosi {$this->promotion->user->name} ke {$this->promotion->toPosition->title} telah diverifikasi HR.",
            'url' => url('/admin/promotions'),
            'icon' => 'heroicon-o-check-badge',
        ];
    }
}
