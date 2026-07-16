<?php

namespace App\Models;

use App\Enums\ReviewType;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = ['review_period_id', 'reviewer_id', 'reviewee_id', 'type', 'score', 'comments', 'submitted_at'];

    protected function casts(): array
    {
        return ['type' => ReviewType::class, 'submitted_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $review): void {
            $reviewer = User::findOrFail($review->reviewer_id);
            $reviewee = User::findOrFail($review->reviewee_id);
            $valid = match ($review->type) {
                ReviewType::ManagerToEmployee => $reviewee->manager_id === $reviewer->id,
                ReviewType::EmployeeToManager => $reviewer->manager_id === $reviewee->id,
                ReviewType::Peer => $reviewer->id !== $reviewee->id && $reviewer->unit_id !== null && $reviewer->unit_id === $reviewee->unit_id,
            };
            if (! $valid) {
                throw new DomainException('Hubungan penilai dan pegawai tidak valid.');
            }
        });

        static::updating(fn () => throw new DomainException('Penilaian yang telah dikirim tidak dapat diubah.'));
        static::deleting(fn () => throw new DomainException('Penilaian yang telah dikirim tidak dapat dihapus.'));
    }

    public function reviewPeriod(): BelongsTo
    {
        return $this->belongsTo(ReviewPeriod::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }
}
