<?php

namespace App\Notifications;

use App\Models\AttendanceRequest;

class AttendanceRequestRejected extends WorkflowNotification
{
    public function __construct(public AttendanceRequest $request) {}

    protected function payload(): array
    {
        return [
            'title' => 'Izin Tugas Luar Ditolak',
            'body' => "Izin tugas luar ke {$this->request->destination_name} ditolak.",
            'url' => url('/app/attendance-requests'),
            'icon' => 'heroicon-o-x-circle',
        ];
    }
}
