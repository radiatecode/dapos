<?php

namespace DA\Admin\Services;

use DA\Admin\DTO\GenerateInvoiceDTO;
use DA\Admin\DTO\RecordPaymentDTO;
use DA\Admin\Enums\InvoiceItemType;
use DA\Admin\Enums\InvoiceStatus;
use DA\Admin\Exceptions\BillingException;
use DA\Admin\Models\Addon;
use DA\Admin\Models\Coupon;
use DA\Admin\Models\Invoice;
use DA\Admin\Models\InvoiceItem;
use DA\Admin\Models\Subscription;
use DA\Admin\Models\SubscriptionItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(
        private CouponService $coupons,
        private PaymentService $payments,
    ) {}

    public function generate(GenerateInvoiceDTO $dto): Invoice
    {
        return $this->transact(function () use ($dto): Invoice {
            $subscription = Subscription::query()
                ->with(['plan.currency', 'items', 'tenant'])
                ->lockForUpdate()
                ->findOrFail($dto->subscription_id);

            if ($subscription->current_period_start === null || $subscription->current_period_end === null) {
                throw new BillingException('This subscription does not have a billing period.');
            }

            if ($subscription->plan?->currency_id === null) {
                throw new BillingException('The subscription plan does not have a currency.');
            }

            $periodStart = $subscription->current_period_start;

            if (Invoice::queries()->openForSubscriptionPeriod($subscription->id, $periodStart) instanceof Invoice) {
                throw new BillingException('An open invoice already exists for this billing period.');
            }

            if (Invoice::queries()->paidForSubscriptionPeriod($subscription->id, $periodStart) instanceof Invoice) {
                throw new BillingException('This billing period has already been paid.');
            }

            $lines = $this->linesFromSubscription($subscription);

            if ($lines === []) {
                throw new BillingException('This subscription has no billable items.');
            }

            $subtotal = '0.00';

            foreach ($lines as $line) {
                $subtotal = $this->money((float) $subtotal + (float) $line['subtotal']);
            }

            $coupon = null;
            $discount = '0.00';

            if (is_string($dto->coupon_code) && $dto->coupon_code !== '') {
                $coupon = $this->coupons->validate(
                    $dto->coupon_code,
                    $subscription->tenant,
                    $subscription,
                    $subtotal,
                );
                $discount = $this->coupons->discountFor($coupon, $subtotal);
            }

            $tax = '0.00';
            $total = $this->money(max(0, (float) $subtotal - (float) $discount + (float) $tax));

            $invoice = new Invoice;
            $invoice->tenant_id = $subscription->tenant_id;
            $invoice->subscription_id = $subscription->id;
            $invoice->invoice_number = $this->nextInvoiceNumber();
            $invoice->status = InvoiceStatus::Open;
            $invoice->billing_period_start = $periodStart;
            $invoice->billing_period_end = $subscription->current_period_end;
            $invoice->subtotal = $subtotal;
            $invoice->discount_amount = $discount;
            $invoice->tax_amount = $tax;
            $invoice->total_amount = $total;
            $invoice->currency_id = $subscription->plan->currency_id;
            $invoice->due_date = $subscription->current_period_end;
            $invoice->save();

            foreach ($lines as $line) {
                $item = new InvoiceItem;
                $item->invoice_id = $invoice->id;
                $item->item_type = $line['item_type'];
                $item->description = $line['description'];
                $item->quantity = $line['quantity'];
                $item->unit_price = $line['unit_price'];
                $item->subtotal = $line['subtotal'];
                $item->discount_amount = '0.00';
                $item->tax_amount = '0.00';
                $item->total_amount = $line['subtotal'];
                $item->reference_type = $line['reference_type'];
                $item->reference_id = $line['reference_id'];
                $item->save();
            }

            if ($coupon instanceof Coupon && (float) $discount > 0) {
                $couponItem = new InvoiceItem;
                $couponItem->invoice_id = $invoice->id;
                $couponItem->item_type = InvoiceItemType::Coupon;
                $couponItem->description = 'Coupon '.$coupon->code;
                $couponItem->quantity = 1;
                $couponItem->unit_price = '0.00';
                $couponItem->subtotal = '0.00';
                $couponItem->discount_amount = $discount;
                $couponItem->tax_amount = '0.00';
                $couponItem->total_amount = $this->money(-1 * (float) $discount);
                $couponItem->reference_type = InvoiceItemType::Coupon->value;
                $couponItem->reference_id = $coupon->id;
                $couponItem->save();

                $this->coupons->redeem($coupon, $invoice, $discount);
            }

            return $invoice->fresh(['items', 'currency', 'tenant', 'subscription.plan', 'couponRedemption.coupon']);
        });
    }

    public function markPaid(Invoice $invoice, RecordPaymentDTO $dto): Invoice
    {
        return $this->payments->recordSuccess($invoice, $dto);
    }

    public function markFailed(Invoice $invoice, RecordPaymentDTO $dto): Invoice
    {
        return $this->payments->recordFailure($invoice, $dto);
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function historyForTenant(int $tenantId): Collection
    {
        return Invoice::queries()->billingHistoryForTenant($tenantId);
    }

    /**
     * @return Collection<int, Invoice>
     */
    public function historyForSubscription(int $subscriptionId): Collection
    {
        return Invoice::queries()->billingHistoryForSubscription($subscriptionId);
    }

    /**
     * @return list<array{item_type: InvoiceItemType, description: string, quantity: int, unit_price: string, subtotal: string, reference_type: string, reference_id: int}>
     */
    private function linesFromSubscription(Subscription $subscription): array
    {
        $addonIds = $subscription->items
            ->filter(fn (SubscriptionItem $item): bool => $item->isAddon())
            ->pluck('reference_id')
            ->all();

        $addons = $addonIds === []
            ? collect()
            : Addon::query()->whereIn('id', $addonIds)->get()->keyBy('id');

        $lines = [];

        foreach ($subscription->items as $item) {
            $quantity = max(1, $item->quantity);
            $unitPrice = $this->money($item->unit_price);
            $type = $item->isPlan() ? InvoiceItemType::Plan : InvoiceItemType::Addon;
            $description = $item->isPlan()
                ? ($subscription->plan?->name ?? 'Plan')
                : ($addons->get($item->reference_id)?->name ?? 'Add-on');

            $lines[] = [
                'item_type' => $type,
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $this->money((float) $unitPrice * $quantity),
                'reference_type' => $type->value,
                'reference_id' => $item->reference_id,
            ];
        }

        return $lines;
    }

    private function nextInvoiceNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ym').'-';
        $latest = Invoice::queries()->latestNumberForPrefix($prefix);

        $sequence = 1;

        if (is_string($latest)) {
            $sequence = ((int) substr($latest, strlen($prefix))) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function money(float|string $amount): string
    {
        return number_format(round((float) $amount, 2), 2, '.', '');
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    private function transact(callable $callback): mixed
    {
        if (DB::transactionLevel() > 0) {
            return $callback();
        }

        return DB::transaction($callback);
    }
}
