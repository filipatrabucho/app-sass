<?php

namespace App\Central\Http\Controllers;

use App\Central\Http\Requests\UpdateTenantModulesRequest;
use App\Central\Http\Resources\TenantModuleResource;
use App\Central\Models\CentralAuditLog;
use App\Core\Licensing\LicenseService;
use App\Core\Tenancy\Models\Tenant;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TenantModuleController
{
    public function index(Tenant $tenant): AnonymousResourceCollection
    {
        return TenantModuleResource::collection($tenant->modules()->orderBy('module_key')->get());
    }

    public function update(UpdateTenantModulesRequest $request, Tenant $tenant, LicenseService $licenses): AnonymousResourceCollection
    {
        $old = $tenant->modules()->get()->map(fn ($m) => (new TenantModuleResource($m))->resolve())->values()->all();

        $updated = $licenses->update($tenant, $request->validated('modules'));

        CentralAuditLog::record('tenant.modules_updated', $tenant, ['modules' => $old], [
            'modules' => $request->validated('modules'),
        ], $tenant->id);

        return TenantModuleResource::collection($updated->sortBy('module_key')->values());
    }
}
