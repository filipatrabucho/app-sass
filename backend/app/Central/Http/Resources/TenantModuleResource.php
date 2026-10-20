<?php

namespace App\Central\Http\Resources;

use App\Core\Licensing\Models\TenantModule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TenantModule
 */
class TenantModuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->module_key,
            'active' => $this->active,
            'effective' => $this->isEffective(),
            'starts_at' => $this->starts_at?->toDateString(),
            'ends_at' => $this->ends_at?->toDateString(),
            'max_users' => $this->max_users,
        ];
    }
}
