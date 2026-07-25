<?php

namespace App\Enums;

enum UserRole: string
{
    case Employee = 'employee';
    case Manager = 'manager';
    case HrAdmin = 'hr_admin';
    case Director = 'director';
    case ItAdmin = 'it_admin';
}
