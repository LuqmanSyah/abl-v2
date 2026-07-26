<?php

namespace App\Notifications;

use App\Models\AttendanceRequest;

class AttendanceRequestAssigned extends WorkflowNotification
{
    public function __construct(public AttendanceRequest $request) {}

    protected function payload(): array
    {
        return [
            'title' => 'Tugas Luar Baru',
            'body' => "Anda mendapat tugas luar ke {$this->request->destination_name}.",
            'url' => url('/app/attendance-requests'),
            'icon' => 'heroicon-o-map-pin',
        ];
    }
}
