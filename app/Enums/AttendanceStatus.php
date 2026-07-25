<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Normal = 'normal';
    case Late = 'late';
    case PendingVerification = 'pending_verification';
    case Rejected = 'rejected';
}
