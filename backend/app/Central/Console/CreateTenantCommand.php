<?php

namespace App\Central\Console;

use App\Central\Services\TenantProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateTenantCommand extends Command
{
    protected $signature = 'tenants:create
        {name : Nome da empresa}
        {subdomain : Subdomínio (ex.: empresa)}
        {--admin-name=Administrador : Nome do admin do cliente}
        {--admin-email= : Email do admin do cliente}
        {--admin-password= : Password (gerada se omitida)}
        {--nif= : NIF}
        {--module=* : Módulos a ativar (ex.: --module=vacations)}';

    protected $description = 'Cria um tenant (BD, migrations, seeds, admin e licenças)';

    public function handle(TenantProvisioner $provisioner): int
    {
        $email = $this->option('admin-email') ?: $this->ask('Email do administrador do cliente');
        $password = $this->option('admin-password') ?: Str::password(14);

        $tenant = $provisioner->create([
            'name' => $this->argument('name'),
            'nif' => $this->option('nif'),
            'contact_email' => $email,
            'subdomain' => strtolower($this->argument('subdomain')),
            'admin' => ['name' => $this->option('admin-name'), 'email' => $email, 'password' => $password],
            'modules' => $this->option('module'),
        ]);

        $this->components->info("Tenant criado: {$tenant->id}");
        $this->components->twoColumnDetail('Domínio', $tenant->primaryDomain());
        $this->components->twoColumnDetail('Admin', $email);
        if (! $this->option('admin-password')) {
            $this->components->twoColumnDetail('Password', $password);
        }
        $this->components->twoColumnDetail('Módulos', implode(', ', $this->option('module')) ?: '—');

        return self::SUCCESS;
    }
}
