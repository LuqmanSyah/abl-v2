<?php

namespace App\Providers\Filament;

use App\Filament\Resources\AttendanceRequestResource;
use App\Filament\Resources\AttendanceResource;
use App\Filament\Resources\CareerPathResource;
use App\Filament\Resources\IndividualDevelopmentPlanResource;
use App\Filament\Resources\LeaveRequestResource;
use App\Filament\Resources\PerformanceReviewResource;
use App\Filament\Resources\ReviewKpiDetailResource;
use App\Filament\Resources\UserSkillResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class EmployeePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('employee')
            ->path('app')
            ->spa()
            ->viteTheme('resources/css/app.css')
            ->resources([
                AttendanceResource::class,
                AttendanceRequestResource::class,
                CareerPathResource::class,
                LeaveRequestResource::class,
                PerformanceReviewResource::class,
                ReviewKpiDetailResource::class,
                IndividualDevelopmentPlanResource::class,
                UserSkillResource::class,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets/Employee'), for: 'App\\Filament\\Widgets\\Employee')
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => view('pwa.register')->render())
            ->userMenuItems([
                Action::make('admin-panel')
                    ->label('Panel Admin')
                    ->icon('heroicon-o-arrow-path')
                    ->url(fn (): string => Filament::getPanel('admin')->getUrl())
                    ->visible(function (): bool {
                        $user = Auth::user();

                        return $user instanceof User
                            && $user->canAccessPanel(Filament::getPanel('admin'));
                    }),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
