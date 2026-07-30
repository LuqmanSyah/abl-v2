<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Valid = 'valid';
    case NeedsReview = 'needs_review';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Valid',
            self::NeedsReview => 'Memerlukan Pemeriksaan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Valid => 'success',
            self::NeedsReview => 'warning',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return array_column(array_map(fn (self $status) => [$status->value, $status->label()], self::cases()), 1, 0);
    }
}
