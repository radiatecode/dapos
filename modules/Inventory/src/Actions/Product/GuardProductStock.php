<?php

namespace DA\Inventory\Actions\Product;

use DA\Inventory\DTO\Product\ProductDTO;
use DA\Inventory\Models\InventoryLayer;
use DA\Inventory\Models\Product;
use DA\Inventory\Services\Decimal;
use Illuminate\Validation\ValidationException;

class GuardProductStock
{
    public function handle(Product $product, ProductDTO $dto): void
    {
        $tenantId = (int) auth()->user()->tenant_id;
        $existing = $product->variants()->get()->keyBy('id');
        $keptIds = [];

        foreach ($dto->variants as $index => $variantDto) {
            if ($variantDto->id === null || ! $existing->has($variantDto->id)) {
                continue;
            }

            $keptIds[] = $variantDto->id;
            $current = $existing->get($variantDto->id);

            if (
                Decimal::compare($current->quantity, $variantDto->quantity) !== 0
                && InventoryLayer::queries()->variantHasLayer($tenantId, $variantDto->id)
            ) {
                throw ValidationException::withMessages([
                    "variants.$index.quantity" => 'The quantity cannot be changed after stock has been received. Use a stock adjustment.',
                ]);
            }
        }

        foreach ($existing->keys()->diff($keptIds) as $removedId) {
            if (InventoryLayer::queries()->variantHasRemaining($tenantId, (int) $removedId)) {
                throw ValidationException::withMessages([
                    'variants' => 'This variant cannot be deleted because it still has stock.',
                ]);
            }
        }

        if ($product->track_inventory && ! $dto->trackInventory && InventoryLayer::queries()->anyRemaining($tenantId, $existing->keys()->all())) {
            throw ValidationException::withMessages([
                'track_inventory' => 'Inventory tracking cannot be turned off while stock remains.',
            ]);
        }
    }
}
