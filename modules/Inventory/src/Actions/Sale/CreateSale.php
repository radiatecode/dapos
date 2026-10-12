<?php

namespace DA\Inventory\Actions\Sale;

use App\Models\User;
use DA\Inventory\DTO\Sale\SaleDTO;
use DA\Inventory\DTO\Stock\DecreaseStockData;
use DA\Inventory\Enums\AllocationSourceType;
use DA\Inventory\Enums\SaleStatus;
use DA\Inventory\Enums\StockMovementType;
use DA\Inventory\Enums\StockReferenceType;
use DA\Inventory\Models\InventoryBalance;
use DA\Inventory\Models\ProductVariant;
use DA\Inventory\Models\Sale;
use DA\Inventory\Models\SaleItem;
use DA\Inventory\Services\Decimal;
use DA\Inventory\Services\InventoryService;
use DA\Inventory\Services\PaymentService;
use DA\Inventory\Services\SaleService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateSale
{
    public function __construct(
        private SaleService $sales,
        private PaymentService $payments,
        private InventoryService $inventory,
        private SalePaymentWriter $paymentWriter,
    ) {}

    public function handle(SaleDTO $dto): Sale
    {
        $exception = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                return $this->checkout($dto);
            } catch (UniqueConstraintViolationException $caught) {
                $exception = $caught;
            }
        }

        throw $exception ?? new RuntimeException('The sale could not be completed.');
    }

    private function checkout(SaleDTO $dto): Sale
    {
        return DB::transaction(function () use ($dto): Sale {
            $actor = $this->actor();
            // $variants = $this->variants($dto);
            // $this->sales->assertSellable($dto, $variants);
            // $saleCalculateData = $this->sales->calculate($dto);
            $paymentAllocationData = $this->payments->extractPaymentLines($dto->payments);

            $sale = new Sale;
            $sale->tenant_id = $actor->tenant_id;
            $sale->store_id = $dto->storeId;
            $sale->customer_id = $dto->customerId;
            $sale->invoice_number = Sale::queries()->nextInvoiceNumber();
            $sale->sale_date = $dto->saleDate;
            $sale->total_items = count($dto->items);
            $sale->subtotal = $dto->subtotal;
            $sale->discount_amount = $dto->discountAmount;
            $sale->tax_amount = $dto->taxAmount;
            $sale->grand_total = $dto->total;
            $sale->paid_amount = $paymentAllocationData['paid_amount'];
            $sale->due_amount = $paymentAllocationData['due_amount'];
            $sale->status = SaleStatus::Completed;
            $sale->payment_status = $this->payments->paymentStatus($dto->total, $paymentAllocationData['paid_amount']);
            $sale->cashier_id = $actor->id;
            $sale->save();

            $items = $this->createItems($sale, $dto);
            $this->lockTrackedBalances((int) $actor->tenant_id, $dto->storeId, $items);
            $this->applyCosts($sale, $items);
            $this->paymentWriter->create($sale, $paymentAllocationData['lines']);

            return $sale;
        });
    }

    /**
     * @param  list<array{subtotal: string, total: string}>  $lines
     * @return list<SaleItem>
     */
    private function createItems(Sale $sale, SaleDTO $dto): array
    {
        $items = [];

        foreach ($dto->items as $index => $item) {
            $row = new SaleItem;
            $row->tenant_id = $sale->tenant_id;
            $row->sale_id = $sale->id;
            $row->product_variant_id = $item->productVariantId;
            $row->product_id = $item->productId;
            $row->product_name = $item->productName;
            $row->variation_name = $item->variationName;
            $row->quantity = $item->quantity;
            $row->unit_price = $item->unitPrice;
            $row->tax_amount = $item->taxAmount;
            $row->subtotal = $item->subtotal;
            $row->total = $item->total;
            $row->cost_price = '0.0000';
            $row->cogs = '0.0000';
            $row->save();

            $items[] = $row;
        }

        return $items;
    }

    /**
     * @param  list<SaleItem>  $items
     */
    private function lockTrackedBalances(int $tenantId, int $storeId, array $items): void
    {
        foreach ($items as $item) {
            $variantId = $item->product_variant_id;

            InventoryBalance::queries()->lockForStoreVariant($tenantId, $storeId, $variantId);
        }
    }

    /**
     * @param  list<SaleItem>  $items
     */
    private function applyCosts(Sale $sale, array $items): void
    {
        foreach ($items as $item) {
            // $oldItem = clone $item;

            $cost = $this->inventory->decrease(new DecreaseStockData(
                storeId: $sale->store_id,
                productVariantId: $item->product_variant_id,
                quantity: Decimal::normalize($item->quantity),
                sourceType: AllocationSourceType::Sale,
                sourceId: $sale->id,
                sourceItemId: $item->id,
                movementType: StockMovementType::Sale,
                referenceType: StockReferenceType::Sale,
                referenceId: $sale->id,
                referenceItemId: $item->id,
                productVariationName: $item->product_name,
            ));
            $item->cost_price = $cost->unitCost;
            $item->cogs = $cost->totalCost;

            $item->save();
        }
    }

    /**
     * @return Collection<int, ProductVariant>
     */
    private function variants(SaleDTO $dto): Collection
    {
        $ids = array_map(
            fn ($item): int => $item->productVariantId,
            $dto->items,
        );

        return ProductVariant::query()
            ->with('product')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    private function tracksInventory(ProductVariant $variant): bool
    {
        return (bool) $variant->product?->track_inventory;
    }

    private function actor(): User
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            throw new RuntimeException('Tenant context is required for sales.');
        }

        return $user;
    }
}
