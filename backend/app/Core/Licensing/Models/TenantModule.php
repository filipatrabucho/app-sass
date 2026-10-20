<?php

namespace App\Core\Licensing\Models;

use App\Core\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Licença de um módulo para um tenant (BD central).
 *
 * @property string $tenant_id
 * @property string $module_key
 * @property bool $active
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int|null $max_users
 */
class TenantModule extends Model
{
    use CentralConnection;

    protected $fillable = ['tenant_id', 'module_key', 'active', 'starts_at', 'ends_at', 'max_users'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'max_users' => 'integer',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_key', 'key');
    }

    /**
     * Ativo e dentro do período de validade (datas inclusivas).
     */
    public function isEffective(?Carbon $at = null): bool
    {
        $today = ($at ?? now())->copy()->startOfDay();

        return $this->active
            && ($this->starts_at === null || $this->starts_at->lte($today))
            && ($this->ends_at === null || $this->ends_at->gte($today));
    }
}
