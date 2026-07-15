<?php

namespace App\Enums;

enum TrainingRequestStatus: string
{
    case PendingManager = 'pending_manager';
    case Rejected = 'rejected';
    case PendingHr = 'pending_hr';
    case Approved = 'approved';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::PendingManager => 'Menunggu Atasan',
            self::Rejected => 'Ditolak',
            self::PendingHr => 'Menunggu HR',
            self::Approved => 'Disetujui',
            self::Completed => 'Selesai',
        };
    }
}
