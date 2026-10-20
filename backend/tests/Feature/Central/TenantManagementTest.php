<?php

use App\Central\Models\CentralAuditLog;
use App\Core\Auth\Models\User;
use App\Core\Tenancy\Models\Tenant;

it('cria um tenant com BD, domínio, admin e módulos', function () {
    $response = $this->actingAsCentralAdmin()->postJson($this->centralUrl('tenants'), [
        'name' => 'Empresa X, Lda.',
        'nif' => '509999999',
        'contact_email' => 'rh@x.pt',
        'subdomain' => 'Empresa-X',
        'admin' => ['name' => 'Ana', 'email' => 'ana@x.pt', 'password' => 'segredo123'],
        'modules' => ['vacations'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Empresa X, Lda.')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.domains.0', 'empresa-x.nossaapp.test')
        ->assertJsonPath('data.modules.0.key', 'vacations');

    $tenant = Tenant::findOrFail($response->json('data.id'));
    expect(file_exists(database_path($tenant->database()->getName())))->toBeTrue();

    $tenant->run(function () {
        $admin = User::where('email', 'ana@x.pt')->firstOrFail();
        expect($admin->hasRole('admin'))->toBeTrue()
            ->and($admin->can('vacations.requests.approve'))->toBeTrue();
    });

    expect(CentralAuditLog::where('action', 'tenant.created')->where('tenant_id', $tenant->id)->exists())->toBeTrue();
});

it('não permite subdomínios repetidos ou reservados', function () {
    $this->createTenant('acme');

    $payload = fn (string $sub) => [
        'name' => 'Outra', 'subdomain' => $sub,
        'admin' => ['name' => 'A', 'email' => 'a@a.pt', 'password' => 'segredo123'],
    ];

    $this->actingAsCentralAdmin()
        ->postJson($this->centralUrl('tenants'), $payload('acme'))
        ->assertStatus(422)->assertJsonPath('code', 'VALIDATION_ERROR')->assertJsonValidationErrors('subdomain');

    $this->postJson($this->centralUrl('tenants'), $payload('admin'))
        ->assertStatus(422)->assertJsonValidationErrors('subdomain');

    expect(Tenant::count())->toBe(1);
});

it('lista, mostra, edita e suspende tenants', function () {
    $tenant = $this->createTenant('acme');
    $this->createTenant('beta');

    $this->actingAsCentralAdmin()
        ->getJson($this->centralUrl('tenants?search=acme'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.total', 1);

    $this->putJson($this->centralUrl("tenants/{$tenant->id}"), ['name' => 'Acme SA'])
        ->assertOk()->assertJsonPath('data.name', 'Acme SA');

    $this->postJson($this->centralUrl("tenants/{$tenant->id}/suspend"))
        ->assertOk()->assertJsonPath('data.status', 'suspended');

    $this->getJson($this->centralUrl("tenants/{$tenant->id}"))
        ->assertOk()->assertJsonPath('data.status', 'suspended');
});

it('lista o catálogo de módulos', function () {
    $this->actingAsCentralAdmin()
        ->getJson($this->centralUrl('modules'))
        ->assertOk()
        ->assertJsonFragment(['key' => 'vacations', 'name' => 'Férias']);
});
