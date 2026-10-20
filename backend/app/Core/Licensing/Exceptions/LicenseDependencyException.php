<?php

namespace App\Core\Licensing\Exceptions;

use App\Core\Http\ApiException;

class LicenseDependencyException extends ApiException
{
    public function __construct(string $message, array $errors)
    {
        parent::__construct($message, 'MODULE_DEPENDENCY', 422, $errors);
    }
}
