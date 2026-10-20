<?php

use App\Core\Notifications\Messages\GenericNotification;

it('lista notificações com contagem de não lidas e marca como lidas', function () {
    $tenant = $this->createTenant('acme');
    $user = $this->actingAsRole($tenant, 'employee');
    $tenant->run(function () use ($user) {
        $user->notify(new GenericNotification('info', 'Um', 'Texto 1'));
        $user->notify(new GenericNotification('info', 'Dois', 'Texto 2', '/perfil'));
    });

    $list = $this->getJson($this->tenantUrl($tenant, 'notifications'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.unread_count', 2)
        ->assertJsonStructure(['data' => [['id', 'kind', 'title', 'body', 'link', 'read_at', 'created_at']]]);

    $id = $list->json('data.0.id');
    $this->postJson($this->tenantUrl($tenant, "notifications/{$id}/read"))->assertOk()->assertJsonPath('unread_count', 1);
    $this->getJson($this->tenantUrl($tenant, 'notifications?unread=1'))->assertJsonCount(1, 'data');
    $this->postJson($this->tenantUrl($tenant, 'notifications/read-all'))->assertOk()->assertJsonPath('unread_count', 0);
});

it('não deixa ler notificações de outro utilizador', function () {
    $tenant = $this->createTenant('acme');
    $other = $this->actingAsRole($tenant, 'employee');
    $tenant->run(fn () => $other->notify(new GenericNotification('info', 'Privada', '...')));
    $id = $tenant->run(fn () => $other->notifications()->first()->id);

    $this->actingAsRole($tenant, 'employee');
    $this->postJson($this->tenantUrl($tenant, "notifications/{$id}/read"))->assertNotFound();
});
