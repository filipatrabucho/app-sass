<?php

namespace Tests;

use App\Central\Models\CentralAdmin;
use App\Central\Services\TenantProvisioner;
use App\Core\Auth\Models\User;
use App\Core\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if ($this->usesDatabase()) {
            Artisan::call('modules:sync');
        }
    }

    protected function tearDown(): void
    {
        if (function_exists('tenancy') && $this->app !== null) {
            tenancy()->end();
        }

        foreach (glob(database_path(config('tenancy.database.prefix').'*')) ?: [] as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    protected function usesDatabase(): bool
    {
        return in_array(RefreshDatabase::class, class_uses_recursive(static::class), true);
    }

    /**
     * Cria um tenant completo (BD, seeds, admin) via TenantProvisioner.
     *
     * @param  list<string>  $modules
     */
    protected function createTenant(string $subdomain = 'acme', array $modules = []): Tenant
    {
        return app(TenantProvisioner::class)->create([
            'name' => ucfirst($subdomain).' Lda.',
            'subdomain' => $subdomain,
            'admin' => ['name' => 'Admin '.$subdomain, 'email' => "admin@{$subdomain}.pt", 'password' => 'password'],
            'modules' => $modules,
        ]);
    }

    protected function tenantUrl(Tenant $tenant, string $path): string
    {
        return 'http://'.$tenant->primaryDomain().'/api/'.ltrim($path, '/');
    }

    protected function centralUrl(string $path): string
    {
        return 'http://'.config('tenancy.central_domains')[0].'/api/central/'.ltrim($path, '/');
    }

    /**
     * Pedido "vindo do SPA" (Referer no próprio host) para ativar a autenticação por cookie do Sanctum.
     */
    protected function fromSpa(string $url): static
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $this->withHeader('Referer', "http://{$host}/");
    }

    protected function tenantUser(Tenant $tenant, string $email): User
    {
        return $tenant->run(fn () => User::where('email', $email)->firstOrFail());
    }

    protected function actingAsCentralAdmin(): static
    {
        return $this->actingAs(CentralAdmin::factory()->create(), 'central');
    }
}
