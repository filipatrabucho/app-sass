<?php

namespace App\Central\Http\Controllers;

use App\Central\Http\Requests\StoreTenantRequest;
use App\Central\Http\Requests\UpdateTenantRequest;
use App\Central\Http\Resources\TenantResource;
use App\Central\Models\CentralAuditLog;
use App\Central\Services\TenantProvisioner;
use App\Core\Tenancy\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TenantController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,suspended'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $tenants = Tenant::query()
            ->with('domains')
            ->when($request->input('search'), function ($query, string $search) {
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('nif', 'like', "%{$search}%")
                    ->orWhereHas('domains', fn ($d) => $d->where('domain', 'like', "%{$search}%")));
            })
            ->when($request->input('status'), fn ($q, string $status) => $q->where('status', $status))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return TenantResource::collection($tenants);
    }

    public function store(StoreTenantRequest $request, TenantProvisioner $provisioner): JsonResponse
    {
        $tenant = $provisioner->create($request->validated());

        return (new TenantResource($tenant))->response()->setStatusCode(201);
    }

    public function show(Tenant $tenant): TenantResource
    {
        return new TenantResource($tenant->load('domains', 'modules'));
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant): TenantResource
    {
        $old = $tenant->only(array_keys($request->validated()));
        $tenant->update($request->validated());
        CentralAuditLog::record('tenant.updated', $tenant, $old, $request->validated(), $tenant->id);

        return new TenantResource($tenant->load('domains', 'modules'));
    }

    public function suspend(Tenant $tenant): TenantResource
    {
        return $this->setStatus($tenant, Tenant::STATUS_SUSPENDED);
    }

    public function activate(Tenant $tenant): TenantResource
    {
        return $this->setStatus($tenant, Tenant::STATUS_ACTIVE);
    }

    private function setStatus(Tenant $tenant, string $status): TenantResource
    {
        $old = $tenant->status;
        $tenant->update(['status' => $status]);
        CentralAuditLog::record('tenant.status_changed', $tenant, ['status' => $old], ['status' => $status], $tenant->id);

        return new TenantResource($tenant->load('domains', 'modules'));
    }
}
