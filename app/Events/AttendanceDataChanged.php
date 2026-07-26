<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceDataChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ?int $userId,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly bool $aggregateAttendance = false,
    ) {}
}
