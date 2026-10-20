<?php

namespace App\Core\Tenancy\Models;

use App\Core\Licensing\Models\TenantModule;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

/**
 * @property string $id
 * @property string $name
 * @property string|null $nif
 * @property string|null $contact_email
 * @property string $status
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public static function getCustomColumns(): array
    {
        return ['id', 'name', 'nif', 'contact_email', 'status', 'created_at', 'updated_at'];
    }

    public function modules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Domínio principal (o primeiro registado).
     */
    public function primaryDomain(): ?string
    {
        return $this->domains->sortBy('id')->first()?->domain;
    }
}
