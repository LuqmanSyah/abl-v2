<?php

namespace App\Enums;

enum ReviewStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Locked = 'locked';
}
