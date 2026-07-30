<?php

namespace App\Providers\Filament;

use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\Attendances\AttendanceResource;
use App\Filament\Resources\DevelopmentPlans\DevelopmentPlanResource;
use App\Filament\Resources\DevelopmentRequests\DevelopmentRequestResource;
use App\Filament\Resources\DutyTrips\DutyTripResource;
use App\Filament\Resources\EmployeeKpis\EmployeeKpiResource;
use App\Filament\Resources\MeritResults\MeritResultResource;
use App\Filament\Resources\Positions\PositionResource;
use App\Filament\Resources\ReviewPeriods\ReviewPeriodResource;
use App\Filament\Resources\Units\UnitResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\AppStats;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('app')
            ->brandName('ABL')
            ->login()
            ->profile(isSimple: false)
            ->assets([
                Css::make('portal-theme', asset('css/portal-filament.css')),
            ])
            ->brandLogo(fn () => view('components.brand-logo'))
            ->favicon(asset('icons/icon-192.svg'))
            ->maxContentWidth('max-w-full')
            ->sidebarCollapsibleOnDesktop()
            ->unsavedChangesAlerts()
            ->databaseTransactions()
            ->pages([Dashboard::class])
            ->resources([
                UserResource::class,
                UnitResource::class,
                PositionResource::class,
                DutyTripResource::class,
                AttendanceResource::class,
                ReviewPeriodResource::class,
                EmployeeKpiResource::class,
                MeritResultResource::class,
                DevelopmentPlanResource::class,
                DevelopmentRequestResource::class,
                ActivityLogResource::class,
            ])
            ->navigationGroups([
                'Organisasi',
                'Operasional',
                'Kinerja',
                'Pengembangan',
                'Laporan',
            ])
            ->widgets([
                AppStats::class,
                AccountWidget::class,
            ])
            ->colors(['primary' => Color::Blue])
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
            ->authMiddleware([Authenticate::class]);
    }
}
