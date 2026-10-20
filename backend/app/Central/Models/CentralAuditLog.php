<?php

namespace App\Central\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class CentralAuditLog extends Model
{
    use CentralConnection;

    public const UPDATED_AT = null;

    protected $fillable = [
        'central_admin_id', 'tenant_id', 'action', 'auditable_type', 'auditable_id',
        'old_values', 'new_values', 'ip',
    ];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array'];
    }

    public static function record(string $action, ?Model $auditable = null, ?array $old = null, ?array $new = null, ?string $tenantId = null): self
    {
        return self::create([
            'central_admin_id' => auth('central')->id(),
            'tenant_id' => $tenantId,
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'ip' => request()?->ip(),
        ]);
    }
}
