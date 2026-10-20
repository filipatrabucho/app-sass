<?php

use App\Core\Licensing\LicenseService;

it('ativa e desativa módulos com datas e invalida a cache de licenças', function () {
    $tenant = $this->createTenant('acme');
    $licenses = app(LicenseService::class);

    expect($licenses->activeModules($tenant))->toBe([]);

    $this->actingAsCentralAdmin()
        ->putJson($this->centralUrl("tenants/{$tenant->id}/modules"), [
            'modules' => [['key' => 'vacations', 'active' => true, 'starts_at' => now()->subDay()->toDateString(), 'max_users' => 50]],
        ])
        ->assertOk()
        ->assertJsonPath('data.0.key', 'vacations')
        ->assertJsonPath('data.0.effective', true)
        ->assertJsonPath('data.0.max_users', 50);

    expect($licenses->activeModules($tenant))->toBe(['vacations']);

    $this->putJson($this->centralUrl("tenants/{$tenant->id}/modules"), [
        'modules' => [['key' => 'vacations', 'active' => false]],
    ])->assertOk()->assertJsonPath('data.0.active', false);

    expect($licenses->activeModules($tenant))->toBe([]);
});

it('não considera ativa uma licença fora do período', function () {
    $tenant = $this->createTenant('acme');

    $this->actingAsCentralAdmin()->putJson($this->centralUrl("tenants/{$tenant->id}/modules"), [
        'modules' => [['key' => 'vacations', 'active' => true, 'ends_at' => now()->subDay()->toDateString()]],
    ])->assertOk()->assertJsonPath('data.0.effective', false);

    expect(app(LicenseService::class)->activeModules($tenant))->toBe([]);
});

it('rejeita módulos inexistentes', function () {
    $tenant = $this->createTenant('acme');

    $this->actingAsCentralAdmin()->putJson($this->centralUrl("tenants/{$tenant->id}/modules"), [
        'modules' => [['key' => 'nao-existe', 'active' => true]],
    ])->assertStatus(422)->assertJsonValidationErrors('modules.0.key');
});
