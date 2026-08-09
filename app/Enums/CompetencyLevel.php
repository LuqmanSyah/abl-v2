<?php

namespace App\Enums;

enum CompetencyLevel: int
{
    case Basic = 1;
    case Guided = 2;
    case Independent = 3;
    case Advanced = 4;
    case Expert = 5;

    public function label(): string
    {
        return match ($this) {
            self::Basic => '1 — Memahami konsep dasar',
            self::Guided => '2 — Menerapkan dengan arahan',
            self::Independent => '3 — Menerapkan secara mandiri',
            self::Advanced => '4 — Membimbing orang lain',
            self::Expert => '5 — Menetapkan strategi dan standar',
        };
    }

    /** @return array<int, string> */
    public static function options(): array
    {
        return array_column(
            array_map(fn (self $level): array => [$level->value, $level->label()], self::cases()),
            1,
            0,
        );
    }
}
