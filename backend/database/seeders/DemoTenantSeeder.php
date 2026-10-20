<?php

namespace Database\Seeders;

use App\Core\Auth\Models\User;
use App\Core\Departments\Models\Department;
use App\Core\Departments\Models\Position;
use App\Core\Employees\Models\Employee;
use App\Core\Holidays\HolidayService;
use App\Core\Holidays\Models\Holiday;
use App\Core\Notifications\Messages\GenericNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Dados FALSOS de demonstração para um tenant (corre dentro do contexto do tenant):
 * 3 departamentos, 15 colaboradores com hierarquia e contas de acesso, feriados do ano
 * corrente e algumas notificações. Todas as passwords: "password".
 *
 *   php artisan tenants:seed --class="Database\Seeders\DemoTenantSeeder" --tenants=<id>
 */
class DemoTenantSeeder extends Seeder
{
    private string $password;

    private int $number = 1000;

    public function run(HolidayService $holidays): void
    {
        if (Employee::query()->exists()) {
            $this->command?->warn('O tenant já tem colaboradores; demo ignorado.');

            return;
        }

        $this->password = Hash::make('password');

        $positions = collect([
            'CEO', 'Diretora de Recursos Humanos', 'Técnico de Recursos Humanos', 'Diretor de Tecnologia',
            'Engenheiro de Software', 'Designer de Produto', 'Diretora Comercial', 'Gestor de Conta',
        ])->mapWithKeys(fn ($name) => [$name => Position::firstOrCreate(['name' => $name])]);

        $adm = Department::create(['name' => 'Administração e RH']);
        $tech = Department::create(['name' => 'Tecnologia', 'parent_id' => $adm->id]);
        $sales = Department::create(['name' => 'Comercial', 'parent_id' => $adm->id]);

        // Topo: liga ao admin criado no provisionamento, se existir.
        $ceo = $this->person('Sofia', 'Almeida', 'admin@demo.pt', 'admin', $adm, $positions['CEO']);
        $hr = $this->person('Marta', 'Ribeiro', 'rh@demo.pt', 'hr', $adm, $positions['Diretora de Recursos Humanos'], $ceo);
        $this->person('Tiago', 'Fernandes', 'tiago.fernandes@demo.pt', 'hr', $adm, $positions['Técnico de Recursos Humanos'], $hr);

        $cto = $this->person('Ricardo', 'Sousa', 'chefe.ti@demo.pt', 'manager', $tech, $positions['Diretor de Tecnologia'], $ceo);
        $this->person('João', 'Pereira', 'colaborador@demo.pt', 'employee', $tech, $positions['Engenheiro de Software'], $cto);
        foreach ([['Beatriz', 'Martins'], ['Miguel', 'Costa'], ['Rita', 'Gonçalves'], ['Pedro', 'Rodrigues']] as $i => [$first, $last]) {
            $position = $i === 3 ? $positions['Designer de Produto'] : $positions['Engenheiro de Software'];
            $this->person($first, $last, null, 'employee', $tech, $position, $cto);
        }

        $cso = $this->person('Inês', 'Carvalho', 'chefe.comercial@demo.pt', 'manager', $sales, $positions['Diretora Comercial'], $ceo);
        foreach ([['André', 'Lopes'], ['Catarina', 'Marques'], ['Nuno', 'Santos'], ['Joana', 'Ferreira'], ['Diogo', 'Oliveira']] as [$first, $last]) {
            $this->person($first, $last, null, 'employee', $sales, $positions['Gestor de Conta'], $cso);
        }

        $adm->update(['manager_employee_id' => $ceo->id]);
        $tech->update(['manager_employee_id' => $cto->id]);
        $sales->update(['manager_employee_id' => $cso->id]);

        // Feriados: nacionais do ano corrente (+ seguinte) e exemplos municipal/empresa.
        $holidays->importNational(now()->year);
        $holidays->importNational(now()->year + 1);
        Holiday::create(['date' => now()->year.'-06-13', 'name' => 'Santo António', 'type' => 'municipal', 'region' => 'Lisboa', 'recurring' => true]);
        Holiday::create(['date' => now()->year.'-12-24', 'name' => 'Véspera de Natal (tolerância)', 'type' => 'company', 'recurring' => true]);

        // Notificações de exemplo.
        User::query()->each(function (User $user) {
            $user->notify(new GenericNotification('welcome', 'Bem-vindo(a)!', 'A sua conta está pronta. Complete o seu perfil.', '/perfil'));
            $user->notify(new GenericNotification('info', 'Feriados atualizados', 'Os feriados nacionais deste ano já estão disponíveis.', null));
        });
        User::where('email', 'colaborador@demo.pt')->first()?->unreadNotifications->first()?->markAsRead();

        $this->command?->info('Demo: 3 departamentos, '.Employee::count().' colaboradores (password: "password").');
    }

    private function person(string $first, string $last, ?string $email, string $role, Department $department, Position $position, ?Employee $manager = null): Employee
    {
        $email ??= strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', "{$first}.{$last}")).'@demo.pt';

        $user = User::firstOrNew(['email' => $email]);
        $user->forceFill(['name' => "{$first} {$last}", 'password' => $user->exists ? $user->password : $this->password, 'active' => true])->save();
        $user->syncRoles([$role]);

        return Employee::factory()->create([
            'user_id' => $user->id,
            'employee_number' => (string) ++$this->number,
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'manager_id' => $manager?->id,
        ]);
    }
}
