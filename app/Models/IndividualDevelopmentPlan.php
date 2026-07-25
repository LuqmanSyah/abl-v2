<?php

namespace App\Models;

use App\Enums\IdpStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndividualDevelopmentPlan extends Model
{
    protected $fillable = [
        'user_id',
        'mentor_id',
        'title',
        'action_plan',
        'progress_percentage',
        'target_completion_date',
        'status',
    ];

    protected $casts = [
        'progress_percentage' => 'integer',
        'target_completion_date' => 'date',
        'status' => IdpStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }
}
