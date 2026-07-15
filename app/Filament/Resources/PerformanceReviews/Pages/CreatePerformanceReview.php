<?php

namespace App\Filament\Resources\PerformanceReviews\Pages;

use App\Enums\ReviewType;
use App\Enums\UserRole;
use App\Filament\Resources\PerformanceReviews\PerformanceReviewResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePerformanceReview extends CreateRecord
{
    protected static string $resource = PerformanceReviewResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        $data['reviewer_id'] = $user->id;
        $data['type'] = $user->role === UserRole::Manager
            ? ReviewType::ManagerToEmployee->value
            : ($user->manager_id === (int) $data['reviewee_id'] ? ReviewType::EmployeeToManager->value : ReviewType::Peer->value);
        $data['submitted_at'] = now();

        return $data;
    }
}
