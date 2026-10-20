<?php

namespace App\Core\Licensing\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Catálogo de módulos vendáveis (BD central). Sincronizado a partir dos module.json.
 *
 * @property string $key
 * @property string $name
 * @property array $requires
 */
class Module extends Model
{
    use CentralConnection;

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'name', 'description', 'version', 'requires'];

    protected function casts(): array
    {
        return ['requires' => 'array'];
    }
}
