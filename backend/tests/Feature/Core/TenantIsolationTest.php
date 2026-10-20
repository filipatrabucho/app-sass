<?php

use App\Core\Auth\Models\User;

it('mantém os dados de cada tenant na sua própria base de dados', function () {
    $a = $this->createTenant('alfa');
    $b = $this->createTenant('beta');

    $a->run(fn () => User::factory()->count(3)->create());

    expect($a->run(fn () => User::count()))->toBe(4)
        ->and($b->run(fn () => User::count()))->toBe(1)
        ->and($b->run(fn () => User::where('email', 'admin@alfa.pt')->exists()))->toBeFalse();
});

it('não permite login de um utilizador de outro tenant', function () {
    $this->createTenant('alfa');
    $b = $this->createTenant('beta');

    $login = $this->tenantUrl($b, 'auth/login');
    $this->fromSpa($login)
        ->postJson($login, ['email' => 'admin@alfa.pt', 'password' => 'password'])
        ->assertStatus(422);
});

it('não aceita noutro tenant uma sessão criada num tenant', function () {
    $a = $this->createTenant('alfa');
    $b = $this->createTenant('beta');

    $login = $this->tenantUrl($a, 'auth/login');
    $this->fromSpa($login)->postJson($login, ['email' => 'admin@alfa.pt', 'password' => 'password'])->assertOk();

    $meA = $this->tenantUrl($a, 'me');
    $this->fromSpa($meA)->getJson($meA)->assertOk();

    // Mesma sessão (o cliente de testes partilha-a) apresentada no tenant B.
    $meB = $this->tenantUrl($b, 'me');
    $this->fromSpa($meB)->getJson($meB)->assertUnauthorized();
});
