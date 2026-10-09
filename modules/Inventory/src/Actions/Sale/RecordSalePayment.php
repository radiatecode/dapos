<?php

namespace DA\Inventory\Actions\Sale;

use DA\Inventory\DTO\Sale\SalePaymentDTO;
use DA\Inventory\Models\Sale;
use DA\Inventory\Services\Decimal;
use DA\Inventory\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordSalePayment
{
    public function __construct(
        private PaymentService $payments,
        private SalePaymentWriter $paymentWriter,
    ) {}

    public function handle(int $saleId, SalePaymentDTO $payment): Sale
    {
        return DB::transaction(function () use ($saleId, $payment): Sale {
            $sale = Sale::queries()->lockForTenant($saleId);

            if (! Decimal::isPositive($sale->due_amount)) {
                throw ValidationException::withMessages([
                    'received_amount' => 'This sale has no balance due.',
                ]);
            }

            $allocated = $this->payments->allocate(Decimal::normalize($sale->due_amount), [$payment]);
            $this->paymentWriter->create($sale, $allocated['lines']);

            $sale->paid_amount = Decimal::add($sale->paid_amount, $allocated['paid_amount']);
            $sale->due_amount = $allocated['due_amount'];
            $sale->payment_status = $this->payments->paymentStatus(
                Decimal::normalize($sale->grand_total),
                Decimal::normalize($sale->paid_amount),
            );
            $sale->save();

            return Sale::queries()->findForDetail($sale->id);
        });
    }
}
