<?php

namespace Database\Seeders;

use App\Central\Models\CentralAdmin;
use App\Central\Services\TenantProvisioner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * Seeder da BD central (dev/demo): admin da plataforma, catálogo de módulos e tenant demo.
 */
class DatabaseSeeder extends Seeder
{
    public function run(TenantProvisioner $provisioner): void
    {
        Artisan::call('modules:sync');

        CentralAdmin::updateOrCreate(
            ['email' => config('platform.central_admin.email')],
            ['name' => 'Administrador', 'password' => config('platform.central_admin.password')],
        );

        if (! Domain::query()->where('domain', TenantProvisioner::domainFor('demo'))->exists()) {
            $provisioner->create([
                'name' => 'Empresa Demo, Lda.',
                'nif' => '500000000',
                'contact_email' => 'rh@demo.pt',
                'subdomain' => 'demo',
                'admin' => ['name' => 'Admin Demo', 'email' => 'admin@demo.pt', 'password' => 'password'],
                'modules' => ['vacations'],
            ]);
        }

        $this->command?->info('Tenant demo: http://'.TenantProvisioner::domainFor('demo').' (admin@demo.pt / password)');
        $this->command?->info('Painel central: http://'.(config('tenancy.central_domains')[0] ?? '').' ('.config('platform.central_admin.email').' / password)');
    }
}
