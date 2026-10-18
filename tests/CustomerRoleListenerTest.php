<?php

declare(strict_types=1);

use Shopper\Core\Events\Orders\OrderCreated;
use Shopper\Listeners\EnsureCustomerRole;
use Shopper\ShopperServiceProvider;

function ensureCustomerRoleListened(): bool
{
    foreach (app('events')->getListeners(OrderCreated::class) as $listener) {
        $reflection = new ReflectionFunction($listener);

        if (($reflection->getStaticVariables()['listener'] ?? null) === EnsureCustomerRole::class) {
            return true;
        }
    }

    return false;
}

function registerShopperProvider(): void
{
    $provider = new ShopperServiceProvider(app());
    (new ReflectionMethod($provider, 'register'))->invoke($provider);
}

it('defaults the customer role on order to true', function (): void {
    expect(config('shopper.admin.customer_role_on_order'))->toBeTrue()
        ->and(config('shopper.models.customer_title_column'))->toBe('first_name');
});

it('listens to OrderCreated with EnsureCustomerRole when the switch is true', function (): void {
    app('events')->forget(OrderCreated::class);
    config(['shopper.admin.customer_role_on_order' => true]);

    registerShopperProvider();

    expect(ensureCustomerRoleListened())->toBeTrue();
});

it('does not register the EnsureCustomerRole listener when the switch is false', function (): void {
    app('events')->forget(OrderCreated::class);
    config(['shopper.admin.customer_role_on_order' => false]);

    registerShopperProvider();

    expect(ensureCustomerRoleListened())->toBeFalse();
});
