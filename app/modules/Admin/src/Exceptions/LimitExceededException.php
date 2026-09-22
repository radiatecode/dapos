<?php

namespace DA\Admin\Exceptions;

class LimitExceededException extends EntitlementException
{
    public function __construct(string $message = 'The plan limit for this feature has been reached.')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return 422;
    }
}
