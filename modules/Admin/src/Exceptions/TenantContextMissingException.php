<?php

namespace DA\Admin\Exceptions;

use RuntimeException;

class TenantContextMissingException extends RuntimeException
{
    public function __construct(string $message = 'Tenant context is missing.')
    {
        parent::__construct($message);
    }
}
