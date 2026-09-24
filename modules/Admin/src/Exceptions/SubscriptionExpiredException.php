<?php

namespace DA\Admin\Exceptions;

class SubscriptionExpiredException extends EntitlementException
{
    public function __construct(string $message = 'This tenant subscription has expired.')
    {
        parent::__construct($message);
    }
}
