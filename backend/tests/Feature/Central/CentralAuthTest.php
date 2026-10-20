<?php

use App\Central\Models\CentralAdmin;

it('autentica um administrador central', function () {
    CentralAdmin::factory()->create(['email' => 'ops@nossaapp.pt']);

    $url = $this->centralUrl('auth/login');
    $this->fromSpa($url)
        ->postJson($url, ['email' => 'ops@nossaapp.pt', 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('admin.email', 'ops@nossaapp.pt');

    $this->assertAuthenticated('central');
});

it('recusa credenciais erradas com o formato de erro standard', function () {
    CentralAdmin::factory()->create(['email' => 'ops@nossaapp.pt']);

    $url = $this->centralUrl('auth/login');
    $this->fromSpa($url)
        ->postJson($url, ['email' => 'ops@nossaapp.pt', 'password' => 'errada'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'INVALID_CREDENTIALS')
        ->assertJsonStructure(['message', 'code', 'errors' => ['email']]);
});

it('exige autenticação central para gerir tenants', function () {
    $this->getJson($this->centralUrl('tenants'))
        ->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED');
});

it('não expõe a API central nos domínios de tenant', function () {
    $tenant = $this->createTenant('acme');

    $this->actingAsCentralAdmin()
        ->getJson('http://'.$tenant->primaryDomain().'/api/central/tenants')
        ->assertNotFound();
});
