<?php

use App\Core\Licensing\Jobs\RequiresModuleLicense;
use App\Core\Licensing\LicenseService;
use Laravel\Sanctum\Sanctum;

it('permite rotas de um módulo licenciado', function () {
    $tenant = $this->createTenant('acme', ['vacations']);
    Sanctum::actingAs($this->tenantUser($tenant, 'admin@acme.pt'));

    $this->getJson($this->tenantUrl($tenant, 'vacations/my'))->assertOk();
});

it('devolve 403 MODULE_NOT_LICENSED em rotas de um módulo inativo', function () {
    $tenant = $this->createTenant('acme');
    Sanctum::actingAs($this->tenantUser($tenant, 'admin@acme.pt'));

    $this->getJson($this->tenantUrl($tenant, 'vacations/my'))
        ->assertForbidden()
        ->assertJsonPath('code', 'MODULE_NOT_LICENSED')
        ->assertJsonPath('errors.module', 'vacations');
});

it('bloqueia o módulo logo após ser desativado e repõe o acesso ao reativar', function () {
    $tenant = $this->createTenant('acme', ['vacations']);
    $licenses = app(LicenseService::class);
    Sanctum::actingAs($this->tenantUser($tenant, 'admin@acme.pt'));
    $url = $this->tenantUrl($tenant, 'vacations/my');

    $this->getJson($url)->assertOk();

    $licenses->update($tenant, [['key' => 'vacations', 'active' => false]]);
    $this->getJson($url)->assertForbidden();
    $this->getJson($this->tenantUrl($tenant, 'me'))->assertJsonPath('active_modules', []);

    $licenses->update($tenant, [['key' => 'vacations', 'active' => true]]);
    $this->getJson($url)->assertOk();
});

it('descarta jobs de módulos não licenciados', function () {
    $tenant = $this->createTenant('acme');
    $ran = false;

    $tenant->run(function () use (&$ran) {
        (new RequiresModuleLicense('vacations'))->handle(new stdClass, function () use (&$ran) {
            $ran = true;
        });
    });
    expect($ran)->toBeFalse();

    app(LicenseService::class)->update($tenant, [['key' => 'vacations', 'active' => true]]);

    $tenant->run(function () use (&$ran) {
        (new RequiresModuleLicense('vacations'))->handle(new stdClass, function () use (&$ran) {
            $ran = true;
        });
    });
    expect($ran)->toBeTrue();
});
