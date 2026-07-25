<?php

namespace App\Enums;

enum PromotionStatus: string
{
    case Proposed = 'proposed';
    case ApprovedByHr = 'approved_by_hr';
    case ApprovedByDirector = 'approved_by_director';
    case Rejected = 'rejected';
    case Expired = 'expired';
}
