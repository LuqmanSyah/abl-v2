<?php

namespace App\Enums;

enum IdpStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
