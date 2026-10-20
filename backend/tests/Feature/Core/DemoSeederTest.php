<?php

use App\Core\Departments\Models\Department;
use App\Core\Employees\Models\Employee;
use App\Core\Holidays\Models\Holiday;
use Database\Seeders\DemoTenantSeeder;

it('o seeder demo cria 3 departamentos, 15 colaboradores com hierarquia e feriados', function () {
    $tenant = $this->createTenant('demo');

    $tenant->run(function () {
        $this->seed(DemoTenantSeeder::class);

        expect(Department::count())->toBe(3)
            ->and(Employee::count())->toBe(15)
            ->and(Employee::whereNull('manager_id')->count())->toBe(1)
            ->and(Employee::whereNull('user_id')->count())->toBe(0)
            ->and(Holiday::where('type', 'national')->count())->toBeGreaterThanOrEqual(13);

        $joao = Employee::where('email', 'colaborador@demo.pt')->first();
        expect($joao->manager->email)->toBe('chefe.ti@demo.pt')
            ->and($joao->user->hasRole('employee'))->toBeTrue();
    });
});
