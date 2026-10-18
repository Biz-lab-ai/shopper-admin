<?php

declare(strict_types=1);

namespace Shopper\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Codeat3\BladePhosphorIcons\BladePhosphorIconsServiceProvider;
use CodeWithDennis\FilamentSelectTree\FilamentSelectTreeServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\Livewire\Partials\DataStoreOverride;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use JaOcero\RadioDeck\RadioDeckServiceProvider;
use Laravel\Passkeys\PasskeysServiceProvider;
use Laravelcm\LivewireSlideOvers\LivewireSlideOverServiceProvider;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsServiceProvider;
use Livewire\LivewireServiceProvider;
use Livewire\Mechanisms\DataStore;
use Mckenziearts\BladeUntitledUIIcons\BladeUntitledUIIconsServiceProvider;
use Milon\Barcode\BarcodeServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Shopper\Cart\CartServiceProvider;
use Shopper\Core\CoreServiceProvider;
use Shopper\Payment\PaymentServiceProvider;
use Shopper\Shipping\ShippingServiceProvider;
use Shopper\ShopperServiceProvider;
use Shopper\Sidebar\SidebarServiceProvider;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;
use Spatie\Permission\PermissionServiceProvider;
use TailwindMerge\Laravel\TailwindMergeServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            ActionsServiceProvider::class,
            BarcodeServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeUntitledUIIconsServiceProvider::class,
            BladePhosphorIconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            CartServiceProvider::class,
            CoreServiceProvider::class,
            FilamentServiceProvider::class,
            PasskeysServiceProvider::class,
            PaymentServiceProvider::class,
            PermissionServiceProvider::class,
            ShippingServiceProvider::class,
            ShopperServiceProvider::class,
            SidebarServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            NotificationsServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            MediaLibraryServiceProvider::class,
            TailwindMergeServiceProvider::class,
            RadioDeckServiceProvider::class,
            FilamentApexChartsServiceProvider::class,
            FilamentSelectTreeServiceProvider::class,
            LivewireSlideOverServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->singleton(DataStore::class, DataStoreOverride::class);
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
