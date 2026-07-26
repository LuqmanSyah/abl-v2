<?php

namespace App\Notifications;

use App\Models\LeaveRequest;

class LeaveRequestRejected extends WorkflowNotification
{
    public function __construct(public LeaveRequest $request) {}

    protected function payload(): array
    {
        return [
            'title' => 'Pengajuan Cuti Ditolak',
            'body' => 'Pengajuan cuti Anda ditolak.',
            'url' => url('/app/leave-requests'),
            'icon' => 'heroicon-o-x-circle',
        ];
    }
}
