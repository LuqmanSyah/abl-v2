<?php

namespace App\Enums;

enum DutyTripStatus: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'info',
            self::Cancelled => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return array_column(array_map(fn (self $status) => [$status->value, $status->label()], self::cases()), 1, 0);
    }
}
