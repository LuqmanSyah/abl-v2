<?php

namespace App\Notifications;

use App\Models\Attendance;

class CheckOutExceptionRejected extends WorkflowNotification
{
    public function __construct(public Attendance $attendance) {}

    protected function payload(): array
    {
        return [
            'title' => 'Exception Check-Out Ditolak',
            'body' => 'Check-out luar radius Anda ditolak atau melewati cutoff.',
            'url' => url('/app/attendances'),
            'icon' => 'heroicon-o-x-circle',
        ];
    }
}
