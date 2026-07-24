<?php

namespace App\Enums;

enum MentoringStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Atasan',
            self::Approved => 'Dijadwalkan',
            self::Rejected => 'Ditolak',
            self::Completed => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Completed => 'info',
        };
    }
}
