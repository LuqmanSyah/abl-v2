<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Normal = 'normal';
    case Late = 'late';
    case Alfa = 'alfa';
    case PendingVerification = 'pending_verification';
    case Rejected = 'rejected';
}
