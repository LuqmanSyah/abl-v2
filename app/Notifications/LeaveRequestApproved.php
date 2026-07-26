<?php

namespace App\Notifications;

use App\Models\LeaveRequest;

class LeaveRequestApproved extends WorkflowNotification
{
    public function __construct(public LeaveRequest $request) {}

    protected function payload(): array
    {
        return [
            'title' => 'Pengajuan Cuti Disetujui',
            'body' => 'Pengajuan cuti Anda telah disetujui.',
            'url' => url('/app/leave-requests'),
            'icon' => 'heroicon-o-calendar-days',
        ];
    }
}
