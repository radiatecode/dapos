<?php

namespace DA\Admin\Exceptions;

class SubscriptionMissingException extends EntitlementException
{
    public function __construct(string $message = 'This tenant does not have a subscription.')
    {
        parent::__construct($message);
    }
}
