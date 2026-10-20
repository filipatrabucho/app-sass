<?php

namespace App\Central\Http\Controllers;

use App\Central\Http\Resources\ModuleResource;
use App\Core\Licensing\Models\Module;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ModuleController
{
    public function index(): AnonymousResourceCollection
    {
        return ModuleResource::collection(Module::query()->orderBy('name')->get());
    }
}
