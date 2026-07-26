<?php

namespace App\Notifications;

use App\Models\PerformanceReview;

class MeritScorePublished extends WorkflowNotification
{
    public function __construct(public PerformanceReview $review) {}

    protected function payload(): array
    {
        return [
            'title' => 'Hasil Merit Dipublikasikan',
            'body' => "Hasil merit periode {$this->review->period} telah dipublikasikan.",
            'url' => url('/app/performance-reviews'),
            'icon' => 'heroicon-o-document-chart-bar',
        ];
    }
}
