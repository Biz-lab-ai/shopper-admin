<?php

declare(strict_types=1);

use Shopper\Actions\Store\Product\SaveProductVariantsAction;
use Shopper\Core\Contracts\StockReserver;
use Shopper\Core\Events\Orders\OrderCancelled;
use Shopper\Core\Listeners\Orders\RestoreOrderStockListener;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\InventoryHistory;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\OrderItem;
use Shopper\Core\Models\Product;
use Shopper\Core\Models\ProductVariant;

beforeEach(function (): void {
    $this->inventory = Inventory::factory()->create(['is_default' => true, 'priority' => 0]);
    $this->second = Inventory::factory()->create(['is_default' => false, 'priority' => 1]);
    $this->product = Product::factory()->create();
    $this->variant = ProductVariant::factory()->create(['product_id' => $this->product->id, 'sku' => 'KEEP-1']);
});

/**
 * @return array<int, array<string, mixed>>
 */
function editedVariant(ProductVariant $variant, int $stock): array
{
    return [[
        'variant_id' => $variant->id,
        'name' => $variant->name,
        'sku' => $variant->sku,
        'price' => 0,
        'stock' => $stock,
        'values' => [],
    ]];
}

describe('VariantSaveKeepsReservationsTest', function (): void {
    it('keeps the reservation of an order through a variant save and restocks it once', function (): void {
        $this->variant->mutateStock($this->inventory->id, 10, event: 'Initial');
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_type' => $this->variant->getMorphClass(),
            'product_id' => $this->variant->id,
            'quantity' => 3,
        ]);
        resolve(StockReserver::class)->reserve($this->variant, 3, $order);

        app(SaveProductVariantsAction::class)(editedVariant($this->variant, 12), $this->product);

        expect($this->variant->getStock())->toBe(12)
            ->and($this->variant->inventoryHistories()->where('reference_id', $order->id)->where('quantity', -3)->count())->toBe(1);

        resolve(RestoreOrderStockListener::class)->handle(new OrderCancelled($order->fresh()));
        resolve(RestoreOrderStockListener::class)->handle(new OrderCancelled($order->fresh()));

        expect($this->variant->getStock())->toBe(15)
            ->and($this->variant->inventoryHistories()->where('reference_id', $order->id)->where('quantity', 3)->count())->toBe(1);
    });

    it('sets the default inventory and zeroes the others without erasing the ledger', function (): void {
        $this->variant->mutateStock($this->inventory->id, 10, event: 'Initial');
        $this->variant->mutateStock($this->second->id, 4, event: 'Initial');

        app(SaveProductVariantsAction::class)(editedVariant($this->variant, 6), $this->product);

        expect($this->variant->stockInventory($this->inventory->id))->toBe(6)
            ->and($this->variant->stockInventory($this->second->id))->toBe(0)
            ->and(InventoryHistory::query()->count())->toBe(4);
    });
});
