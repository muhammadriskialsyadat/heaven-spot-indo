<?php
// app/Providers/Filament/AdminPanelProvider.php
namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\HtmlString;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->brandName('HEAVEN SPOT INDO')
            ->brandLogo(fn () => new HtmlString('
                <div style="text-align: center;">
                    <div style="font-size: 1.2rem; font-weight: 900; line-height: 1.2;">
                        <span style="color: #2563eb;">HEAVEN</span>
                        <span style="color: #9333ea; margin: 0 2px;">SPOT</span>
                        <span style="color: #059669;">INDO</span>
                    </div>
                </div>
            '))
            ->brandLogoHeight('2rem')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
    Widgets\AccountWidget::class,
    \App\Filament\Widgets\StatsOverviewWidget::class,
    \App\Filament\Widgets\LowStockWidget::class,
    \App\Filament\Widgets\RecentStockMovementsWidget::class,
    //\App\Filament\Widgets\RecentTransactionsWidget::class,
     // TAMBAHKAN INI
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
            ])
            ->navigationGroups([
                'Master Data',
                'Inventory',
                'Transaksi',
                'Laporan',
            ]);
    }
}