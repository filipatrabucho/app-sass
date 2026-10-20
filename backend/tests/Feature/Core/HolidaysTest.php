<?php

use App\Core\Holidays\Models\Holiday;
use App\Core\Holidays\PortugueseHolidays;

it('calcula a Páscoa e os feriados móveis', function () {
    expect(PortugueseHolidays::easter(2026)->toDateString())->toBe('2026-04-05')
        ->and(PortugueseHolidays::easter(2025)->toDateString())->toBe('2025-04-20');

    $dates = collect(PortugueseHolidays::forYear(2026))->pluck('name', 'date');
    expect($dates)->toHaveCount(13)
        ->and($dates['2026-04-03'])->toBe('Sexta-feira Santa')
        ->and($dates['2026-06-04'])->toBe('Corpo de Deus');
});

it('importa feriados nacionais sem duplicar e projeta os recorrentes', function () {
    $tenant = $this->createTenant('acme');
    $this->actingAsRole($tenant, 'hr');

    $this->postJson($this->tenantUrl($tenant, 'holidays/import-national'), ['year' => 2026])
        ->assertOk()->assertJsonPath('created', 13);
    $this->postJson($this->tenantUrl($tenant, 'holidays/import-national'), ['year' => 2026])
        ->assertOk()->assertJsonPath('created', 0);
    // 2027: os fixos já são recorrentes; só entram os 3 móveis.
    $this->postJson($this->tenantUrl($tenant, 'holidays/import-national'), ['year' => 2027])
        ->assertOk()->assertJsonPath('created', 3);

    $this->getJson($this->tenantUrl($tenant, 'holidays?year=2027'))
        ->assertOk()
        ->assertJsonCount(13, 'data')
        ->assertJsonFragment(['date' => '2027-12-25', 'name' => 'Natal']);
});

it('RH cria feriados municipais e filtra por concelho; colaborador não gere', function () {
    $tenant = $this->createTenant('acme');
    $this->actingAsRole($tenant, 'hr');

    $this->postJson($this->tenantUrl($tenant, 'holidays'), ['date' => '2026-06-13', 'name' => 'Santo António', 'type' => 'municipal'])
        ->assertStatus(422)->assertJsonValidationErrors('region');
    $this->postJson($this->tenantUrl($tenant, 'holidays'), ['date' => '2026-06-13', 'name' => 'Santo António', 'type' => 'municipal', 'region' => 'Lisboa'])
        ->assertCreated();
    $tenant->run(fn () => Holiday::create(['date' => '2026-06-24', 'name' => 'São João', 'type' => 'municipal', 'region' => 'Porto']));

    $this->getJson($this->tenantUrl($tenant, 'holidays?year=2026&region=Lisboa'))->assertJsonCount(1, 'data');

    $this->actingAsRole($tenant, 'employee');
    $this->getJson($this->tenantUrl($tenant, 'holidays?year=2026'))->assertOk()->assertJsonCount(2, 'data');
    $this->postJson($this->tenantUrl($tenant, 'holidays'), ['date' => '2026-01-02', 'name' => 'X', 'type' => 'company'])->assertForbidden();
});
