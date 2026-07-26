<?php

namespace App\Filament\Resources\PromotionResource\Pages;

use App\Enums\PromotionStatus;
use App\Exceptions\BusinessRuleException;
use App\Filament\Resources\PromotionResource;
use App\Models\Position;
use App\Models\User;
use App\Services\ReadinessScoreService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListPromotions extends ListRecords
{
    protected static string $resource = PromotionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(function (array $data): array {
                    $user = User::query()
                        ->where('manager_id', Auth::id())
                        ->findOrFail($data['user_id']);

                    if ($user->position_id === (int) $data['to_position_id']) {
                        throw new BusinessRuleException('Posisi tujuan harus berbeda dari posisi saat ini.');
                    }

                    return [
                        ...$data,
                        'from_position_id' => $user->position_id,
                        'proposed_by' => Auth::id(),
                        'readiness_score' => app(ReadinessScoreService::class)
                            ->calculate($user, Position::findOrFail($data['to_position_id'])),
                        'status' => PromotionStatus::Proposed,
                    ];
                }),
        ];
    }
}
