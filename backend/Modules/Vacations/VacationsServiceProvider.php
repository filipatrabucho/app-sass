<?php

namespace Modules\Vacations;

use App\Core\Modules\ModuleServiceProvider;

class VacationsServiceProvider extends ModuleServiceProvider
{
    public const KEY = 'vacations';

    protected function moduleKey(): string
    {
        return self::KEY;
    }
}
