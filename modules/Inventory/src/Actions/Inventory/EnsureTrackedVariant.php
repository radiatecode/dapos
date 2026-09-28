<?php

namespace DA\Inventory\Actions\Inventory;

use DA\Inventory\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

class EnsureTrackedVariant
{
    public function handle(int $variantId): ProductVariant
    {
        $variant = ProductVariant::query()
            ->with('product')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->find($variantId);

        if (! $variant instanceof ProductVariant || $variant->product === null || ! $variant->product->track_inventory) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'The selected product does not track inventory.',
            ]);
        }

        return $variant;
    }
}
