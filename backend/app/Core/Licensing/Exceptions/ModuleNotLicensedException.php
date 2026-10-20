<?php

namespace App\Core\Licensing\Exceptions;

use App\Core\Http\ApiException;

class ModuleNotLicensedException extends ApiException
{
    public function __construct(public readonly string $moduleKey)
    {
        parent::__construct(__('licensing.module_not_licensed'), 'MODULE_NOT_LICENSED', 403, ['module' => $moduleKey]);
    }
}
