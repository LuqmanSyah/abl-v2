<?php

namespace App\Enums;

enum UserRole: string
{
    case Employee = 'pegawai';
    case Manager = 'atasan';
    case Hr = 'hr';

    public function label(): string
    {
        return match ($this) {
            self::Employee => 'Pegawai',
            self::Manager => 'Atasan',
            self::Hr => 'Admin SDM/HR',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return array_column(
            array_map(fn (self $role) => [$role->value, $role->label()], self::cases()),
            1,
            0,
        );
    }
}
