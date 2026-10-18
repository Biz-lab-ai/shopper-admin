<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Shopper\Core\CoreServiceProvider;
use Shopper\Core\Enum\DiscountEligibility;
use Shopper\Core\Models\Address;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\OrderRefund;
use Shopper\Core\Search\CustomerIndexer;
use Shopper\Discounts\DiscountEligibilityFieldRegistry;

class ThrowingCustomerStub extends Model
{
    protected $table = 'customer_key_stubs';

    protected $guarded = [];

    public $timestamps = false;

    protected static function booted(): void
    {
        static::retrieved(function (): never {
            throw new RuntimeException('The customer model must not be hydrated.');
        });
    }

    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('is_customer', true);
    }
}

beforeEach(function (): void {
    Schema::create('customer_key_stubs', function ($table): void {
        $table->id();
        $table->boolean('is_customer')->default(true);
    });

    config(['shopper.models.customer' => ThrowingCustomerStub::class]);
});

it('shows the replaced customer model in the core relations', function (): void {
    expect((new Order)->customer()->getRelated())->toBeInstanceOf(ThrowingCustomerStub::class)
        ->and((new Address)->user()->getRelated())->toBeInstanceOf(ThrowingCustomerStub::class)
        ->and((new OrderRefund)->customer()->getRelated())->toBeInstanceOf(ThrowingCustomerStub::class);
});

it('indexes the replaced customer model', function (): void {
    expect(config('shopper.search.indexers.'.ThrowingCustomerStub::class))->toBeNull();

    $provider = new CoreServiceProvider(app());
    (new ReflectionMethod($provider, 'registerCustomerIndexer'))->invoke($provider);

    expect(config('shopper.search.indexers.'.ThrowingCustomerStub::class))->toBe(CustomerIndexer::class);
});

it('resolves discount customer ids through the replaced model without hydrating it', function (): void {
    DB::table('customer_key_stubs')->insert([
        ['id' => 1, 'is_customer' => true],
        ['id' => 2, 'is_customer' => false],
        ['id' => 3, 'is_customer' => true],
    ]);

    $ids = app(DiscountEligibilityFieldRegistry::class)
        ->resolveIds(DiscountEligibility::Customers->value, [1, 2, 3, 99]);

    expect($ids)->toBe([1, 3]);
});
