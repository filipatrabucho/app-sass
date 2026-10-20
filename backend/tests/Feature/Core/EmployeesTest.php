<?php

use App\Core\Audit\Models\AuditLog;
use App\Core\Auth\Models\User;
use App\Core\Departments\Models\Department;
use App\Core\Employees\Models\Employee;
use App\Core\Notifications\Messages\WelcomeNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->tenant = $this->createTenant('acme');
});

it('RH cria um colaborador com conta de acesso, role e email de boas-vindas', function () {
    Notification::fake();
    $this->actingAsRole($this->tenant, 'hr');
    $department = $this->tenant->run(fn () => Department::create(['name' => 'Tecnologia']));

    $response = $this->postJson($this->tenantUrl($this->tenant, 'employees'), [
        'first_name' => 'Ana',
        'last_name' => 'Silva',
        'email' => 'ana@acme.pt',
        'department_id' => $department->id,
        'iban' => 'PT50000201231234567890154',
        'create_account' => true,
        'role' => 'manager',
    ])->assertCreated()
        ->assertJsonPath('data.full_name', 'Ana Silva')
        ->assertJsonPath('data.department.name', 'Tecnologia')
        ->assertJsonPath('data.user.roles', ['manager'])
        ->assertJsonPath('data.iban', 'PT50000201231234567890154');

    $this->tenant->run(function () use ($response) {
        $user = User::where('email', 'ana@acme.pt')->firstOrFail();
        expect($user->hasRole('manager'))->toBeTrue();
        Notification::assertSentTo($user, WelcomeNotification::class);

        $audit = AuditLog::where('auditable_type', 'employee')->where('auditable_id', $response->json('data.id'))->firstOrFail();
        expect($audit->action)->toBe('created')->and($audit->new_values['iban'])->toBe('***');
    });
});

it('não permite que RH atribua o role admin', function () {
    $this->actingAsRole($this->tenant, 'hr');

    $this->postJson($this->tenantUrl($this->tenant, 'employees'), [
        'first_name' => 'X', 'last_name' => 'Y', 'email' => 'x@acme.pt', 'create_account' => true, 'role' => 'admin',
    ])->assertStatus(422)->assertJsonValidationErrors('role');
});

it('chefia vê só a sua equipa e sem dados sensíveis; colaborador não lista', function () {
    $manager = $this->actingAsRole($this->tenant, 'manager');
    [$mine, $other] = $this->tenant->run(fn () => [
        Employee::factory()->create(['manager_id' => $manager->employee->id]),
        Employee::factory()->create(),
    ]);

    $this->getJson($this->tenantUrl($this->tenant, 'employees'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $mine->id)
        ->assertJsonMissingPath('data.0.iban');

    $this->getJson($this->tenantUrl($this->tenant, "employees/{$mine->id}"))->assertOk()->assertJsonMissingPath('data.iban');
    $this->getJson($this->tenantUrl($this->tenant, "employees/{$other->id}"))->assertForbidden();
    $this->putJson($this->tenantUrl($this->tenant, "employees/{$mine->id}"), ['phone' => '1'])->assertForbidden();

    $this->actingAsRole($this->tenant, 'employee');
    $this->getJson($this->tenantUrl($this->tenant, 'employees'))->assertForbidden()->assertJsonPath('code', 'FORBIDDEN');
});

it('impede ciclos na hierarquia', function () {
    $this->actingAsRole($this->tenant, 'hr');
    [$boss, $sub] = $this->tenant->run(function () {
        $boss = Employee::factory()->create();

        return [$boss, Employee::factory()->create(['manager_id' => $boss->id])];
    });

    $this->putJson($this->tenantUrl($this->tenant, "employees/{$boss->id}"), ['manager_id' => $sub->id])
        ->assertStatus(422)->assertJsonValidationErrors('manager_id');
    $this->putJson($this->tenantUrl($this->tenant, "employees/{$boss->id}"), ['manager_id' => $boss->id])
        ->assertStatus(422);
});

it('apagar um colaborador desativa a conta e termina as sessões', function () {
    $this->actingAsRole($this->tenant, 'hr');
    $target = $this->tenant->run(function () {
        $user = User::factory()->create();
        $user->assignRole('employee');

        return Employee::factory()->create(['user_id' => $user->id])->load('user');
    });

    $this->deleteJson($this->tenantUrl($this->tenant, "employees/{$target->id}"))->assertNoContent();

    $this->tenant->run(function () use ($target) {
        expect(Employee::withTrashed()->find($target->id)->trashed())->toBeTrue()
            ->and(User::find($target->user_id)->active)->toBeFalse();
    });

    $this->actingAs($this->tenant->run(fn () => User::find($target->user_id)), 'sanctum');
    $this->getJson($this->tenantUrl($this->tenant, 'me'))->assertUnauthorized();
});

it('pesquisa leve para selects', function () {
    $this->actingAsRole($this->tenant, 'hr', ['first_name' => 'Zé', 'last_name' => 'Ninguém']);

    $this->getJson($this->tenantUrl($this->tenant, 'employees/lookup?search=Zé'))
        ->assertOk()
        ->assertJsonPath('data.0.full_name', 'Zé Ninguém');
});
