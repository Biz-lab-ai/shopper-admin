<?php

declare(strict_types=1);

namespace Shopper\Actions\Store\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Shopper\Actions\Store\InitialQuantityInventory;
use Shopper\Core\Contracts\StockTransactionRunner;
use Shopper\Core\Models\Contracts\ProductVariant;
use Throwable;

final class CreateNewVariant
{
    /**
     * @param  array<string, mixed>  $data
     *
     * @throws Throwable
     */
    public function __invoke(array $data): ProductVariant
    {
        // A retried conflict starts again from the caller's data, so the
        // variant is created once, by the attempt that commits
        $variantId = resolve(StockTransactionRunner::class)->retry(
            static fn (): mixed => app(self::class)->attempt($data),
        );

        /** @var Model&ProductVariant $variant */
        $variant = resolve(ProductVariant::class)::query()->findOrFail($variantId);

        return $variant;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function attempt(array $data): mixed
    {
        $attributes = Arr::except($data, ['quantity', 'prices', 'values']);

        $attributes['position'] = (int) resolve(ProductVariant::class)::query()
            ->where('product_id', $attributes['product_id'])
            ->max('position') + 1;

        /** @var Model&ProductVariant $variant */
        $variant = resolve(ProductVariant::class)::query()->create($attributes);

        if ($pricing = data_get($data, 'prices')) {
            app()->call(SavePricingAction::class, [
                'model' => $variant,
                'pricing' => $pricing,
            ]);
        }

        if ($valueIds = data_get($data, 'values')) {
            $variant->values()->sync(array_values($valueIds));
        }

        /** @var int $quantity */
        $quantity = data_get($data, 'quantity');

        if ($quantity > 0) {
            app()->call(InitialQuantityInventory::class, [
                'quantity' => $quantity,
                'product' => $variant,
            ]);
        }

        return $variant->getKey();
    }
}
