<?php

namespace App\Notifications;

use App\Models\AttendanceRequest;

class AttendanceRequestApproved extends WorkflowNotification
{
    public function __construct(public AttendanceRequest $request) {}

    protected function payload(): array
    {
        return [
            'title' => 'Izin Tugas Luar Disetujui',
            'body' => "Izin tugas luar ke {$this->request->destination_name} telah disetujui.",
            'url' => url('/app/attendance-requests'),
            'icon' => 'heroicon-o-check-circle',
        ];
    }
}
