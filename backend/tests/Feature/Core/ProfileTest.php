<?php

use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->tenant = $this->createTenant('acme');
});

it('devolve a ficha do colaborador em /me', function () {
    $this->actingAsRole($this->tenant, 'employee', ['first_name' => 'Rui', 'last_name' => 'Melo']);

    $this->getJson($this->tenantUrl($this->tenant, 'me'))
        ->assertOk()
        ->assertJsonPath('employee.full_name', 'Rui Melo')
        ->assertJsonStructure(['employee' => ['iban', 'address', 'nif']]);
});

it('o colaborador edita os seus contactos mas não o departamento', function () {
    $user = $this->actingAsRole($this->tenant, 'employee');

    $this->putJson($this->tenantUrl($this->tenant, 'me/profile'), [
        'phone' => '912345678',
        'address' => ['street' => 'Rua A, 1', 'postal_code' => '1000-001', 'city' => 'Lisboa'],
        'iban' => 'PT50000000000000000000000',
        'department_id' => 99,
        'locale' => 'en',
    ])->assertOk()
        ->assertJsonPath('employee.phone', '912345678')
        ->assertJsonPath('employee.address.city', 'Lisboa')
        ->assertJsonPath('employee.department_id', $user->employee->department_id)
        ->assertJsonPath('user.locale', 'en');
});

it('o colaborador carrega a sua fotografia, guardada no armazenamento do tenant', function () {
    $this->actingAsRole($this->tenant, 'employee');

    $response = $this->post($this->tenantUrl($this->tenant, 'me/avatar'), [
        'avatar' => UploadedFile::fake()->image('eu.png', 200, 200),
    ], ['Accept' => 'application/json'])->assertOk();

    $url = $response->json('employee.avatar_url');
    expect($url)->toContain('/api/employees/');

    $id = $response->json('employee.id');
    $this->get($this->tenantUrl($this->tenant, "employees/{$id}/avatar"))->assertOk();
    expect(glob(base_path("storage/tenants/{$this->tenant->id}/app/avatars/*")))->toHaveCount(1);
});

it('rejeita ficheiros que não são imagens', function () {
    $this->actingAsRole($this->tenant, 'employee');

    $this->post($this->tenantUrl($this->tenant, 'me/avatar'), [
        'avatar' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'),
    ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors('avatar');
});
