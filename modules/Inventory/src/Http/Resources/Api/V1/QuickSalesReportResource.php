<?php

namespace DA\Inventory\Http\Resources\Api\V1;

use DA\Inventory\Services\Decimal;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property-read object{
 *     sale_date: string,
 *     quick_sales_count: int|string,
 *     total_items: int|string,
 *     total_amount: string|float,
 *     cash_amount: string|float,
 *     card_amount: string|float,
 *     bank_transfer_amount: string|float,
 *     mobile_banking_amount: string|float
 * } $resource
 */
class QuickSalesReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sale_date' => Carbon::parse($this->resource->sale_date)->toDateString(),
            'quick_sales_count' => (int) $this->resource->quick_sales_count,
            'total_items' => (int) $this->resource->total_items,
            'total_amount' => Decimal::normalize($this->resource->total_amount),
            'cash_amount' => Decimal::normalize($this->resource->cash_amount),
            'card_amount' => Decimal::normalize($this->resource->card_amount),
            'bank_transfer_amount' => Decimal::normalize($this->resource->bank_transfer_amount),
            'mobile_banking_amount' => Decimal::normalize($this->resource->mobile_banking_amount),
        ];
    }
}
