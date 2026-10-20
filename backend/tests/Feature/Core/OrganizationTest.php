<?php

use App\Core\Departments\Models\Department;
use App\Core\Employees\Models\Employee;

beforeEach(function () {
    $this->tenant = $this->createTenant('acme');
});

it('RH gere departamentos em árvore e impede ciclos', function () {
    $this->actingAsRole($this->tenant, 'hr');

    $root = $this->postJson($this->tenantUrl($this->tenant, 'departments'), ['name' => 'Direção'])->assertCreated()->json('data');
    $child = $this->postJson($this->tenantUrl($this->tenant, 'departments'), ['name' => 'TI', 'parent_id' => $root['id']])
        ->assertCreated()->assertJsonPath('data.parent_id', $root['id'])->json('data');

    $this->putJson($this->tenantUrl($this->tenant, "departments/{$root['id']}"), ['parent_id' => $child['id']])
        ->assertStatus(422)->assertJsonValidationErrors('parent_id');

    $this->deleteJson($this->tenantUrl($this->tenant, "departments/{$root['id']}"))
        ->assertStatus(422)->assertJsonPath('code', 'DEPARTMENT_NOT_EMPTY');

    $this->deleteJson($this->tenantUrl($this->tenant, "departments/{$child['id']}"))->assertNoContent();
    $this->getJson($this->tenantUrl($this->tenant, 'departments'))->assertOk()->assertJsonCount(1, 'data');
});

it('colaboradores veem departamentos mas não os gerem', function () {
    $this->tenant->run(fn () => Department::create(['name' => 'TI']));
    $this->actingAsRole($this->tenant, 'employee');

    $this->getJson($this->tenantUrl($this->tenant, 'departments'))->assertOk()->assertJsonCount(1, 'data');
    $this->postJson($this->tenantUrl($this->tenant, 'departments'), ['name' => 'X'])->assertForbidden();
});

it('RH gere cargos e não apaga cargos em uso', function () {
    $this->actingAsRole($this->tenant, 'hr');

    $id = $this->postJson($this->tenantUrl($this->tenant, 'positions'), ['name' => 'Analista'])->assertCreated()->json('data.id');
    $this->postJson($this->tenantUrl($this->tenant, 'positions'), ['name' => 'Analista'])->assertStatus(422);

    $this->tenant->run(fn () => Employee::factory()->create(['position_id' => $id]));
    $this->deleteJson($this->tenantUrl($this->tenant, "positions/{$id}"))->assertStatus(422)->assertJsonPath('code', 'POSITION_IN_USE');
});

it('o admin tem acesso a tudo, incluindo a auditoria', function () {
    $this->actingAsRole($this->tenant, 'admin');
    $this->postJson($this->tenantUrl($this->tenant, 'departments'), ['name' => 'TI'])->assertCreated();

    $this->getJson($this->tenantUrl($this->tenant, 'audit-logs?auditable_type=department'))
        ->assertOk()
        ->assertJsonPath('data.0.action', 'created')
        ->assertJsonPath('data.0.auditable_type', 'department');

    $this->actingAsRole($this->tenant, 'hr');
    $this->getJson($this->tenantUrl($this->tenant, 'audit-logs'))->assertForbidden();
});
