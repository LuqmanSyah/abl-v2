<?php

namespace App\Enums;

enum LeaveType: string
{
    case Sick = 'sick';
    case PaidLeave = 'paid_leave';
    case Permit = 'permit';
}
