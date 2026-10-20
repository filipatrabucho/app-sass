<?php

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Http\Resources\MeResource;
use Illuminate\Http\Request;

class MeController
{
    public function show(Request $request): MeResource
    {
        return new MeResource($request->user());
    }
}
