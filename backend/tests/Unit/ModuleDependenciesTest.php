<?php

use App\Core\Licensing\Exceptions\LicenseDependencyException;
use App\Core\Licensing\LicenseService;
use App\Core\Modules\ModuleRegistry;

beforeEach(function () {
    $this->service = new LicenseService(new ModuleRegistry(base_path('tests/Fixtures/Modules')));
});

it('lê os module.json', function () {
    $registry = new ModuleRegistry(base_path('tests/Fixtures/Modules'));

    expect($registry->all()->keys()->all())->toBe(['alpha', 'beta'])
        ->and($registry->find('alpha')->requires)->toBe(['beta']);
});

it('aceita módulos com as dependências ativas', function () {
    $this->service->assertDependencies(['alpha', 'beta']);
    $this->service->assertDependencies(['beta']);
})->throwsNoExceptions();

it('recusa ativar um módulo sem as dependências', function () {
    $this->service->assertDependencies(['alpha']);
})->throws(LicenseDependencyException::class);

it('explica qual a dependência em falta', function () {
    try {
        $this->service->assertDependencies(['alpha']);
    } catch (LicenseDependencyException $e) {
        expect($e->errorCode)->toBe('MODULE_DEPENDENCY')
            ->and($e->status)->toBe(422)
            ->and($e->errors['alpha'][0])->toContain('beta');

        return;
    }

    $this->fail('Esperava LicenseDependencyException');
});
