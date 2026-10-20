<?php

namespace App\Core\Auth\Models;

use App\Core\Audit\Concerns\Auditable;
use App\Core\Employees\Models\Employee;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Utilizador de um tenant (BD do tenant).
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $locale
 * @property bool $active
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $guard_name = 'web';

    protected $fillable = ['name', 'email', 'password', 'locale', 'active', 'last_login_at'];

    protected $hidden = ['password', 'remember_token'];

    /** @var list<string> */
    protected array $auditExclude = ['last_login_at'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
