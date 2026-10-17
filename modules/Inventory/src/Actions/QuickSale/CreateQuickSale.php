<?php

namespace DA\Inventory\Actions\QuickSale;

use App\Models\User;
use DA\Inventory\DTO\QuickSale\QuickSaleDTO;
use DA\Inventory\Models\QuickSale;
use DA\Inventory\Models\QuickSaleItem;
use DA\Inventory\Services\QuickSaleService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateQuickSale
{
    public function __construct(private QuickSaleService $quickSales) {}

    public function handle(QuickSaleDTO $dto): QuickSale
    {
        $totals = $this->quickSales->calculate($dto);
        $exception = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                return DB::transaction(function () use ($dto, $totals): QuickSale {
                    $actor = $this->actor();

                    $quickSale = new QuickSale;
                    $quickSale->tenant_id = $actor->tenant_id;
                    $quickSale->store_id = $dto->storeId;
                    $quickSale->invoice_number = QuickSale::queries()->nextInvoiceNumber();
                    $quickSale->sale_date = $dto->saleDate;
                    $quickSale->total_items = $totals->totalItems;
                    $quickSale->grand_total = $totals->grandTotal;
                    $quickSale->payment_method = $dto->paymentMethod;
                    $quickSale->received_amount = $dto->receivedAmount;
                    $quickSale->change_amount = $totals->changeAmount;
                    $quickSale->reference_number = $dto->referenceNumber;
                    $quickSale->note = $dto->note;
                    $quickSale->cashier_id = $actor->id;
                    $quickSale->save();

                    foreach ($totals->lines as $index => $line) {
                        $item = new QuickSaleItem;
                        $item->tenant_id = $quickSale->tenant_id;
                        $item->quick_sale_id = $quickSale->id;
                        $item->title = $line['title'];
                        $item->unit_amount = $line['unit_amount'];
                        $item->quantity = $line['quantity'];
                        $item->amount = $line['amount'];
                        $item->sort_order = $index;
                        $item->save();
                    }

                    return $quickSale->load(['store', 'cashier', 'items']);
                });
            } catch (UniqueConstraintViolationException $caught) {
                $exception = $caught;
            }
        }

        throw $exception ?? new RuntimeException('The quick sale could not be completed.');
    }

    private function actor(): User
    {
        $user = auth()->user();

        if (! $user instanceof User || $user->tenant_id === null) {
            throw new RuntimeException('Tenant context is required for quick sales.');
        }

        return $user;
    }
}
