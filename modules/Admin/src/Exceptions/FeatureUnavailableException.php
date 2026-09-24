<?php

namespace DA\Admin\Exceptions;

class FeatureUnavailableException extends EntitlementException
{
    public function __construct(string $message = 'This feature is not available on the current plan.')
    {
        parent::__construct($message);
    }
}
