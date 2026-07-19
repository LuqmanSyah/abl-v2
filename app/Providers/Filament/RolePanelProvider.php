<?php

namespace App\Providers\Filament;

use App\Filament\AvatarProviders\OrangeAvatarProvider;
use App\Filament\Pages\EditProfile;
use App\Http\Middleware\HandleForbiddenPanelPage;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

abstract class RolePanelProvider extends PanelProvider
{
    protected function basePanel(Panel $panel): Panel
    {
        return $panel
            ->login(fn () => redirect()->route('login'))
            ->profile(EditProfile::class, isSimple: false)
            ->defaultAvatarProvider(OrangeAvatarProvider::class)
            ->assets([
                Css::make('portal-theme', asset('css/portal-filament.css')),
            ])
            ->darkMode(true, true)
            ->brandLogo(fn () => view('components.brand-logo'))
            ->favicon(asset('icons/icon-192.svg'))
            ->maxContentWidth('max-w-full')
            ->sidebarCollapsibleOnDesktop()
            ->unsavedChangesAlerts()
            ->databaseTransactions()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([Dashboard::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                HandleForbiddenPanelPage::class,
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
