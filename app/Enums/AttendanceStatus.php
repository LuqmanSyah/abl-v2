<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Valid = 'valid';
    case OutsideRadius = 'outside_radius';
    case Late = 'late';
    case PendingSync = 'pending_sync';
    case NeedsReview = 'needs_review';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Valid',
            self::OutsideRadius => 'Di Luar Radius',
            self::Late => 'Terlambat',
            self::PendingSync => 'Menunggu Sinkronisasi',
            self::NeedsReview => 'Memerlukan Pemeriksaan',
        };
    }
}
