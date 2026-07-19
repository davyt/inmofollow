<?php

namespace App\Providers\Filament;

use App\Models\Company;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DavytPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $company = Company::find(config('inmofollow.default_company_id', 1));

        return $panel
            ->default()
            ->id('davyt')
            ->path('davyt')
            ->login()
            ->darkMode(true, isForced: true)
            ->font('Inter')
            ->colors([
                'primary' => $company?->brand_primary_color
                    ? Color::hex($company->brand_primary_color)
                    : Color::Amber,
            ])
            ->when(
                $company?->brand_logo_path,
                fn (Panel $p) => $p->brandLogo($this->brandingUrl($company->brand_logo_path))
                    ->brandLogoHeight('2.25rem'),
            )
            ->when(
                $company?->brand_favicon_path,
                fn (Panel $p) => $p->favicon($this->brandingUrl($company->brand_favicon_path)),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => '<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,500;0,700;1,500&display=swap" rel="stylesheet"><style>.fi-header-heading{font-family:"Lora",serif;}</style>',
            )
            ->navigationGroups([
                NavigationGroup::make('Comercial'),
                NavigationGroup::make('Comunicación'),
                NavigationGroup::make('Automatizaciones'),
                NavigationGroup::make('Configuración'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s');
    }

    private function brandingUrl(string $path): string
    {
        return Storage::disk('branding')->exists($path)
            ? Storage::disk('branding')->url($path)
            : '';
    }
}
