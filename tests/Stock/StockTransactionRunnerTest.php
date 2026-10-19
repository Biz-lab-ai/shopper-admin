<?php

declare(strict_types=1);

use Shopper\Actions\Store\Product\CreateNewVariant;
use Shopper\Actions\Store\Product\SaveProductVariantsAction;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Product;

beforeEach(function (): void {
    Inventory::factory()->create(['is_default' => true, 'priority' => 0]);
    $this->runner = recordingStockRunner();
});

describe('StockTransactionRunnerTest', function (): void {
    it('saves the variants of a product through the runner retry', function (): void {
        $product = Product::factory()->create();

        app(SaveProductVariantsAction::class)([[
            'variant_id' => null,
            'name' => 'Small',
            'sku' => 'RUN-S',
            'price' => 0,
            'stock' => 2,
            'values' => [],
        ]], $product);

        expect($this->runner->calls[0])->toBe('retry:__invoke');
    });

    it('creates a variant through the runner retry', function (): void {
        $product = Product::factory()->create();

        app(CreateNewVariant::class)(['product_id' => $product->id, 'name' => 'Large', 'sku' => 'RUN-L', 'quantity' => 2]);

        expect($this->runner->calls[0])->toBe('retry:__invoke');
    });
});
