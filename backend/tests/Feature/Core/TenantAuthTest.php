<?php

use App\Core\Auth\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

it('faz login no subdomínio do tenant e devolve /me com módulos ativos', function () {
    $tenant = $this->createTenant('acme', ['vacations']);

    $login = $this->tenantUrl($tenant, 'auth/login');
    $this->fromSpa($login)
        ->postJson($login, ['email' => 'admin@acme.pt', 'password' => 'password'])
        ->assertOk();

    $me = $this->tenantUrl($tenant, 'me');
    $this->fromSpa($me)->getJson($me)
        ->assertOk()
        ->assertJsonPath('user.email', 'admin@acme.pt')
        ->assertJsonPath('roles', ['admin'])
        ->assertJsonPath('active_modules', ['vacations'])
        ->assertJsonPath('tenant.name', 'Acme Lda.')
        ->assertJsonPath('employee', null)
        ->assertJson(fn ($json) => $json->where('permissions', fn ($p) => collect($p)->contains('vacations.requests.approve'))->etc());

    expect($this->tenantUser($tenant, 'admin@acme.pt')->last_login_at)->not->toBeNull();
});

it('recusa login com credenciais erradas ou utilizador inativo', function () {
    $tenant = $this->createTenant('acme');
    $tenant->run(fn () => User::factory()->inactive()->create(['email' => 'off@acme.pt']));

    $login = $this->tenantUrl($tenant, 'auth/login');
    $this->fromSpa($login)
        ->postJson($login, ['email' => 'admin@acme.pt', 'password' => 'errada'])
        ->assertStatus(422)->assertJsonPath('code', 'INVALID_CREDENTIALS');

    $this->fromSpa($login)
        ->postJson($login, ['email' => 'off@acme.pt', 'password' => 'password'])
        ->assertStatus(422)->assertJsonPath('code', 'INVALID_CREDENTIALS');
});

it('exige sessão em /me', function () {
    $tenant = $this->createTenant('acme');

    $this->getJson($this->tenantUrl($tenant, 'me'))
        ->assertUnauthorized()
        ->assertJsonPath('code', 'UNAUTHENTICATED');
});

it('termina a sessão', function () {
    $tenant = $this->createTenant('acme');
    $login = $this->tenantUrl($tenant, 'auth/login');
    $this->fromSpa($login)->postJson($login, ['email' => 'admin@acme.pt', 'password' => 'password'])->assertOk();

    $logout = $this->tenantUrl($tenant, 'auth/logout');
    $this->fromSpa($logout)->postJson($logout)->assertNoContent();

    $this->assertGuest('web');
});

it('envia link de reposição de password para o domínio do tenant', function () {
    Notification::fake();
    $tenant = $this->createTenant('acme');

    $url = $this->tenantUrl($tenant, 'auth/forgot-password');
    $this->fromSpa($url)->postJson($url, ['email' => 'admin@acme.pt'])->assertOk();

    $user = $this->tenantUser($tenant, 'admin@acme.pt');
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use ($user, $tenant) {
        $link = $tenant->run(fn () => $n->toMail($user)->actionUrl);

        return str_contains($link, 'acme.nossaapp.test') && str_contains($link, '/reset-password?token=');
    });
});

it('bloqueia a API de um tenant suspenso', function () {
    $tenant = $this->createTenant('acme');
    $tenant->update(['status' => 'suspended']);

    $login = $this->tenantUrl($tenant, 'auth/login');
    $this->fromSpa($login)
        ->postJson($login, ['email' => 'admin@acme.pt', 'password' => 'password'])
        ->assertForbidden()
        ->assertJsonPath('code', 'TENANT_SUSPENDED');
});

it('não serve a API de tenant em domínios desconhecidos', function () {
    $this->getJson('http://inexistente.nossaapp.test/api/me')->assertNotFound();
});
