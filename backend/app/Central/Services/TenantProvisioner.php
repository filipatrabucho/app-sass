<?php

namespace App\Central\Services;

use App\Central\Models\CentralAuditLog;
use App\Core\Auth\Models\User;
use App\Core\Licensing\LicenseService;
use App\Core\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Database\Models\Domain;
use Throwable;

/**
 * Cria um tenant completo: registo central, domínio, BD (criada + migrations + seeds
 * pelo pipeline do TenancyServiceProvider), utilizador admin e licenças iniciais.
 */
class TenantProvisioner
{
    public function __construct(private readonly LicenseService $licenses) {}

    public static function domainFor(string $subdomain): string
    {
        return $subdomain.'.'.config('platform.tenant_base_domain');
    }

    /**
     * @param  array{name: string, nif?: ?string, contact_email?: ?string, subdomain: string,
     *               admin: array{name: string, email: string, password: string}, modules?: list<string>}  $data
     */
    public function create(array $data): Tenant
    {
        $domain = self::domainFor($data['subdomain']);

        if (Domain::query()->where('domain', $domain)->exists()) {
            throw ValidationException::withMessages(['subdomain' => __('central.subdomain_taken')]);
        }

        $tenant = Tenant::create([
            'name' => $data['name'],
            'nif' => $data['nif'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        try {
            $tenant->domains()->create(['domain' => $domain]);

            $tenant->run(function () use ($data) {
                $admin = User::create([
                    'name' => $data['admin']['name'],
                    'email' => $data['admin']['email'],
                    'password' => $data['admin']['password'],
                ]);
                $admin->assignRole('admin');
            });

            if (! empty($data['modules'])) {
                $this->licenses->update($tenant, array_map(
                    fn (string $key) => ['key' => $key, 'active' => true],
                    $data['modules'],
                ));
            }
        } catch (Throwable $e) {
            Log::error('Falha ao criar tenant; a reverter.', ['tenant' => $tenant->id, 'error' => $e->getMessage()]);
            $tenant->delete();

            throw $e;
        }

        CentralAuditLog::record('tenant.created', $tenant, null, [
            'name' => $tenant->name, 'domain' => $domain, 'modules' => $data['modules'] ?? [],
        ], $tenant->id);

        return $tenant->load('domains', 'modules');
    }
}
