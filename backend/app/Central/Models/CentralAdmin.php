<?php

namespace App\Central\Models;

use Database\Factories\CentralAdminFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Administrador da plataforma (nós). Só existe na BD central.
 */
class CentralAdmin extends Authenticatable
{
    /** @use HasFactory<CentralAdminFactory> */
    use CentralConnection, HasFactory;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
        ];
    }

    protected static function newFactory(): CentralAdminFactory
    {
        return CentralAdminFactory::new();
    }
}
