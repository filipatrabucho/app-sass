<?php

namespace App\Core\Holidays\Models;

use App\Core\Audit\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $date
 * @property string $name
 * @property string $type
 * @property bool $recurring
 */
class Holiday extends Model
{
    use Auditable;

    public const TYPES = ['national', 'municipal', 'company'];

    protected $fillable = ['date', 'name', 'type', 'region', 'recurring'];

    protected function casts(): array
    {
        return ['date' => 'date', 'recurring' => 'boolean'];
    }
}
