<?php

namespace DA\Admin\Services\Billing;

interface PaymentProcessor
{
    public function settle(PaymentRequest $request): PaymentOutcome;
}
