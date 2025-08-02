<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Http\Middleware\EnsureAdminPanelAccessible;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        FilamentColor::register([
            'primary' => Color::hex('#6e73f7'),
            'danger' => Color::Rose,
            'gray' => Color::Slate,
            'info' => Color::Blue,
            'success' => Color::Emerald,
            'warning' => Color::Orange,
        ]);

        return $panel
            ->id('admin')
            ->path('admin')
            ->brandLogo(function () {
                return asset('linanok.svg');
            })
            ->login()
            ->profile(isSimple: false)
            ->passwordReset()
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->unsavedChangesAlerts()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->navigationItems([
                NavigationItem::make('App Performance (Pulse)')
                    ->visible(fn () => request()->user()->can('viewPulse'))
                    ->url(fn () => route('pulse'))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-chart-bar')
                    ->group('DevOps')
                    ->sort(3),

                NavigationItem::make('Queue Monitoring (Horizon)')
                    ->visible(fn () => request()->user()->can('viewHorizon'))
                    ->url(fn () => route('horizon.index'))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-bolt')
                    ->group('DevOps')
                    ->sort(4),
            ])
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EnsureAdminPanelAccessible::class,
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
            ])
            ->renderHook(PanelsRenderHook::BODY_END, fn (): string => Blade::render('components.copyright'));
    }
}
