<?php

namespace App\Core\Auth\Http\Resources;

use App\Core\Auth\Models\User;
use App\Core\Licensing\LicenseService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class MeResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        $tenant = tenant();

        return [
            'user' => [
                'id' => $this->id,
                'name' => $this->name,
                'email' => $this->email,
                'locale' => $this->locale,
            ],
            // Preenchido na Fase 2 (colaboradores).
            'employee' => null,
            'roles' => $this->getRoleNames()->values(),
            'permissions' => $this->getAllPermissions()->pluck('name')->sort()->values(),
            'active_modules' => app(LicenseService::class)->activeModules($tenant),
            'tenant' => [
                'id' => $tenant->getTenantKey(),
                'name' => $tenant->name,
            ],
        ];
    }
}
