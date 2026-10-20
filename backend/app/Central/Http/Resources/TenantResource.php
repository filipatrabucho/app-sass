<?php

namespace App\Central\Http\Resources;

use App\Core\Tenancy\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Tenant
 */
class TenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nif' => $this->nif,
            'contact_email' => $this->contact_email,
            'status' => $this->status,
            'domains' => $this->whenLoaded('domains', fn () => $this->domains->pluck('domain')),
            'modules' => TenantModuleResource::collection($this->whenLoaded('modules')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
