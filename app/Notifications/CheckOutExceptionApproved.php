<?php

namespace App\Notifications;

use App\Models\Attendance;

class CheckOutExceptionApproved extends WorkflowNotification
{
    public function __construct(public Attendance $attendance) {}

    protected function payload(): array
    {
        return [
            'title' => 'Exception Check-Out Disetujui',
            'body' => 'Check-out luar radius Anda telah disetujui.',
            'url' => url('/app/attendances'),
            'icon' => 'heroicon-o-check-circle',
        ];
    }
}
