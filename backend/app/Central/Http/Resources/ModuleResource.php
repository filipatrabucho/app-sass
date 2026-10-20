<?php

namespace App\Central\Http\Resources;

use App\Core\Licensing\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Module
 */
class ModuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description,
            'version' => $this->version,
            'requires' => $this->requires ?? [],
        ];
    }
}
