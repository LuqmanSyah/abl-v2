<?php

namespace App\Enums;

enum ReviewType: string
{
    case ManagerToEmployee = 'manager_to_employee';
    case EmployeeToManager = 'employee_to_manager';
    case Peer = 'peer';

    public function label(): string
    {
        return match ($this) {
            self::ManagerToEmployee => 'Atasan ke Pegawai',
            self::EmployeeToManager => 'Pegawai ke Atasan',
            self::Peer => 'Rekan Kerja',
        };
    }

    public static function options(): array
    {
        return array_column(array_map(fn (self $type) => [$type->value, $type->label()], self::cases()), 1, 0);
    }
}
