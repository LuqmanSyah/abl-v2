<?php

namespace App\Enums;

enum DailySummaryStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case Alfa = 'alfa';
    case Leave = 'leave';
    case Holiday = 'holiday';
    case MissingCheckout = 'missing_checkout';
}
