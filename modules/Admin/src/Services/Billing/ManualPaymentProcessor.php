<?php

namespace DA\Admin\Services\Billing;

use DA\Admin\Enums\PaymentStatus;
use Illuminate\Support\Str;

class ManualPaymentProcessor implements PaymentProcessor
{
    public function settle(PaymentRequest $request): PaymentOutcome
    {
        $succeeded = $request->intendedStatus === PaymentStatus::Succeeded;

        return new PaymentOutcome(
            status: $request->intendedStatus,
            transactionId: $request->transactionId ?: 'manual_'.Str::lower((string) Str::ulid()),
            paidAt: $succeeded ? now() : null,
        );
    }
}
