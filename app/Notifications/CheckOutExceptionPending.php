<?php

namespace App\Notifications;

use App\Models\Attendance;

class CheckOutExceptionPending extends WorkflowNotification
{
    public function __construct(public Attendance $attendance) {}

    protected function payload(): array
    {
        return [
            'title' => 'Check-Out Perlu Verifikasi',
            'body' => "Check-out luar radius {$this->attendance->user->name} perlu diverifikasi.",
            'url' => url('/admin/attendances'),
            'icon' => 'heroicon-o-exclamation-triangle',
        ];
    }
}
