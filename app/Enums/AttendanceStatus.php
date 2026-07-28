<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Valid = 'valid';
    case OutsideRadius = 'outside_radius';
    case Late = 'late';
    case NeedsReview = 'needs_review';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Valid',
            self::OutsideRadius => 'Di Luar Radius',
            self::Late => 'Terlambat',
            self::NeedsReview => 'Memerlukan Pemeriksaan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Valid => 'success',
            self::Late, self::NeedsReview => 'warning',
            self::OutsideRadius => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return array_column(array_map(fn (self $status) => [$status->value, $status->label()], self::cases()), 1, 0);
    }
}
