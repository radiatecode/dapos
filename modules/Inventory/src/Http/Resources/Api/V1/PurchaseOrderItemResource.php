<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin PurchaseOrderItem
 */
class PurchaseOrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_order_id' => $this->purchase_order_id,
            'product_variant_id' => $this->product_variant_id,
            'sku' => $this->whenLoaded('variant', fn () => $this->variant?->sku),
            'image_url' => $this->whenLoaded('variant', function (): ?string {
                $path = $this->variant?->image;

                if ((! is_string($path) || $path === '') && $this->variant?->relationLoaded('product')) {
                    $path = $this->variant?->product?->image;
                }

                return is_string($path) && $path !== ''
                    ? Storage::disk('public')->url($path)
                    : null;
            }),
            'variant' => $this->whenLoaded('variant', fn (): array => [
                'id' => $this->variant->id,
                'sku' => $this->variant->sku,
                'name' => $this->variant->name,
                'product' => $this->variant->relationLoaded('product') && $this->variant->product !== null ? [
                    'id' => $this->variant->product->id,
                    'name' => $this->variant->product->name,
                ] : null,
            ]),
            'quantity' => $this->quantity,
            'unit_cost' => $this->unit_cost,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'total' => $this->total,
        ];
    }
}
