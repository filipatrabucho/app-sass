<?php

namespace App\Core\Employees\Services;

use App\Core\Auth\Models\User;
use App\Core\Employees\Models\Employee;
use App\Core\Notifications\Messages\WelcomeNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Criação/edição de colaboradores, incluindo a conta de acesso (users) e o role.
 */
class EmployeeService
{
    public const ACCOUNT_FIELDS = ['create_account', 'role', 'active'];

    /**
     * @param  array<string, mixed>  $data  campos do colaborador + create_account/role
     */
    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $employee = Employee::create(Arr::except($data, self::ACCOUNT_FIELDS));

            if (! empty($data['create_account'])) {
                $this->createAccount($employee, $data['role'] ?? 'employee');
            }

            return $employee;
        });
    }

    public function update(Employee $employee, array $data): Employee
    {
        if (array_key_exists('manager_id', $data)) {
            $this->assertValidManager($employee, $data['manager_id']);
        }

        return DB::transaction(function () use ($employee, $data) {
            $employee->update(Arr::except($data, self::ACCOUNT_FIELDS));

            if (! empty($data['create_account']) && $employee->user_id === null) {
                $this->createAccount($employee, $data['role'] ?? 'employee');
            } elseif ($employee->user !== null) {
                $user = $employee->user;
                $user->fill(array_filter([
                    'name' => $employee->full_name,
                    'email' => $employee->email ?: null,
                ]));
                if (array_key_exists('active', $data)) {
                    $user->active = (bool) $data['active'];
                }
                if ($employee->status === Employee::STATUS_INACTIVE) {
                    $user->active = false;
                }
                $user->save();

                if (! empty($data['role'])) {
                    $user->syncRoles([$data['role']]);
                }
            }

            return $employee->refresh();
        });
    }

    /**
     * Soft delete do colaborador; a conta de acesso fica desativada (os dados mantêm-se).
     */
    public function delete(Employee $employee): void
    {
        DB::transaction(function () use ($employee) {
            $employee->user?->update(['active' => false]);
            $employee->update(['status' => Employee::STATUS_INACTIVE]);
            $employee->delete();
        });
    }

    private function createAccount(Employee $employee, string $role): User
    {
        if (blank($employee->email)) {
            throw ValidationException::withMessages(['email' => __('employees.email_required_for_account')]);
        }
        if (User::where('email', $employee->email)->exists()) {
            throw ValidationException::withMessages(['email' => __('validation.unique', ['attribute' => 'email'])]);
        }

        $user = User::create([
            'name' => $employee->full_name,
            'email' => $employee->email,
            'password' => Str::password(32),
        ]);
        $user->assignRole($role);
        $employee->update(['user_id' => $user->id]);

        // O colaborador define a password pelo link de boas-vindas.
        $token = Password::broker('users')->createToken($user);
        $user->notify(new WelcomeNotification($token));

        return $user;
    }

    public function assertValidManager(Employee $employee, ?int $managerId): void
    {
        if ($managerId === null) {
            return;
        }

        if ($managerId === $employee->id || in_array($managerId, $employee->subordinateIds(), true)) {
            throw ValidationException::withMessages(['manager_id' => __('employees.manager_cycle')]);
        }
    }
}
