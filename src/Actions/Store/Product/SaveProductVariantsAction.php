<?php

declare(strict_types=1);

namespace Shopper\Actions\Store\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Shopper\Core\Contracts\StockTransactionRunner;
use Shopper\Core\Models\Contracts\Inventory;
use Shopper\Core\Models\Contracts\Product;
use Shopper\Core\Models\Contracts\ProductVariant;
use Throwable;

final class SaveProductVariantsAction
{
    /**
     * @param  array<string, mixed>  $variants
     * @param  Model&Product  $product
     * @return array<string, mixed>
     *
     * @throws Throwable
     */
    public function __invoke(array $variants, Product $product): array
    {
        $productId = (int) $product->getKey();

        // A retried conflict starts again from the caller's variants and a
        // freshly loaded product: nothing of the rolled back attempt is reused
        return resolve(StockTransactionRunner::class)->retry(
            static fn (): array => app(self::class)->attempt($variants, $productId),
        );
    }

    /**
     * @param  array<string, mixed>  $variants
     * @return array<string, mixed>
     */
    private function attempt(array $variants, int $productId): array
    {
        /** @var Model&Product $product */
        $product = resolve(Product::class)::query()->findOrFail($productId);

        // Delete variants that are no longer in the list
        $existingVariantIds = collect($variants)
            ->pluck('variant_id')
            ->filter()
            ->values();

        /** @var Collection<int, Model&ProductVariant> $variantsToDelete */
        $variantsToDelete = $product->variants()
            ->when($existingVariantIds->isNotEmpty(), fn ($query): mixed => $query->whereNotIn('id', $existingVariantIds))
            ->when($existingVariantIds->isEmpty(), fn ($query): mixed => $query)
            ->get();

        $variantsToDelete->each(
            /** @param Model&ProductVariant $variant */
            fn (ProductVariant $variant) => $variant->delete()
        );

        $position = (int) $product->variants()->max('position');

        foreach ($variants as $key => $variantState) {
            /** @var Model&ProductVariant $variant */
            $variant = $variantState['variant_id']
                ? resolve(ProductVariant::class)::query()->findOrFail($variantState['variant_id'])
                : resolve(ProductVariant::class)::query()->create([
                    'name' => $variantState['name'],
                    'product_id' => $product->id,
                    'sku' => $variantState['sku'],
                    'position' => ++$position,
                ]);

            $variants[$key]['variant_id'] = $variant->id;

            $price = (int) $variantState['price'];

            if ($price > 0) {
                /** @var int $defaultCurrencyId */
                $defaultCurrencyId = shopper_setting('default_currency_id');

                $existingCompare = $variant->prices()
                    ->where('currency_id', $defaultCurrencyId)
                    ->value('compare_amount');

                $variant->prices()->updateOrCreate(
                    ['currency_id' => $defaultCurrencyId],
                    [
                        'amount' => $price,
                        // A compare price at or below the selling price is a misleading strikethrough.
                        'compare_amount' => $existingCompare !== null && (int) $existingCompare > $price
                            ? (int) $existingCompare
                            : null,
                    ],
                );
            }

            /** @var int $stock */
            $stock = data_get($variantState, 'stock');

            if ($stock > 0) {
                $this->setStock($variant, $stock);
            }

            $variant->values()->sync($variantState['values']);
        }

        return $variants;
    }

    /**
     * The edited stock becomes the quantity of the default inventory and the
     * other inventories go to zero, all by ledger movements: the ledger and
     * the open reservations of a live variant are kept.
     *
     * @param  Model&ProductVariant  $variant
     */
    private function setStock(ProductVariant $variant, int $stock): void
    {
        /** @var (Model&Inventory)|null $default */
        $default = resolve(Inventory::class)::query()->scopes('default')->first();

        if ($default === null) {
            return;
        }

        $event = __('shopper::pages/products.inventory.initial');

        $variant->setStock($stock, $default->id, event: $event);

        $variant->stockLevels()
            ->where('inventory_id', '!=', $default->id)
            ->where('quantity', '!=', 0)
            ->pluck('inventory_id')
            ->each(fn (int|string $inventoryId) => $variant->setStock(0, (int) $inventoryId, event: $event));
    }
}
