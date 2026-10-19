<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Shopper\Actions\Store\Product\CreateNewVariant;
use Shopper\Actions\Store\Product\SaveProductVariantsAction;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\InventoryHistory;
use Shopper\Core\Models\Product;
use Shopper\Core\Models\ProductVariant;
use Shopper\Tests\Stubs\Conflict;

beforeEach(function (): void {
    $this->inventory = Inventory::factory()->create(['is_default' => true, 'priority' => 0]);
    $this->runner = recordingStockRunner();
});

/**
 * Throw the snapshot conflict once, right after the first stock movement.
 */
function conflictAfterFirstMovement(): void
{
    $thrown = false;

    Event::listen('eloquent.created: '.InventoryHistory::class, function () use (&$thrown): void {
        if (! $thrown) {
            $thrown = true;

            throw Conflict::snapshot();
        }
    });
}

describe('StockRetryBoundaryTest', function (): void {
    it('finishes a variant save conflicted after its first movement as if nothing failed', function (): void {
        $product = Product::factory()->create();
        $existing = ProductVariant::factory()->create(['product_id' => $product->id, 'sku' => 'RETRY-OLD']);
        $existing->mutateStock($this->inventory->id, 3, event: 'Initial');
        $variants = [
            ['variant_id' => $existing->id, 'name' => 'Old', 'sku' => 'RETRY-OLD', 'price' => 0, 'stock' => 7, 'values' => []],
            ['variant_id' => null, 'name' => 'New', 'sku' => 'RETRY-NEW', 'price' => 0, 'stock' => 5, 'values' => []],
        ];
        $before = $variants;
        conflictAfterFirstMovement();
        $this->runner->attempted = 0;

        $saved = app(SaveProductVariantsAction::class)($variants, $product);

        $created = ProductVariant::query()->where('sku', 'RETRY-NEW')->sole();

        expect($this->runner->attempted)->toBe(2)
            ->and($variants)->toBe($before)
            ->and($saved[1]['variant_id'])->toBe($created->id)
            ->and($existing->getStock())->toBe(7)
            ->and($created->getStock())->toBe(5)
            ->and(InventoryHistory::query()->count())->toBe(3);
    });

    it('finishes a variant creation conflicted after its first movement as if nothing failed', function (): void {
        $product = Product::factory()->create();
        $data = ['product_id' => $product->id, 'name' => 'Medium', 'sku' => 'RETRY-M', 'quantity' => 4, 'values' => []];
        $before = $data;
        conflictAfterFirstMovement();
        $this->runner->attempted = 0;

        $variant = app(CreateNewVariant::class)($data);

        expect($this->runner->attempted)->toBe(2)
            ->and($data)->toBe($before)
            ->and(ProductVariant::query()->where('sku', 'RETRY-M')->count())->toBe(1)
            ->and($variant->getStock())->toBe(4)
            ->and(InventoryHistory::query()->count())->toBe(1);
    });
});
