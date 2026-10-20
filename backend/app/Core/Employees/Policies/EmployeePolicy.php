<?php

namespace App\Core\Employees\Policies;

use App\Core\Auth\Models\User;
use App\Core\Employees\Models\Employee;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('core.employees.view');
    }

    /**
     * RH vê todos; chefias veem a sua equipa (direta e indireta); todos se veem a si próprios.
     */
    public function view(User $user, Employee $employee): bool
    {
        if ($user->can('core.employees.manage') || $employee->user_id === $user->id) {
            return true;
        }

        $self = $user->employee;

        return $self !== null && $user->can('core.employees.view') && $employee->isManagedBy($self);
    }

    public function create(User $user): bool
    {
        return $user->can('core.employees.manage');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('core.employees.manage');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('core.employees.manage') && $employee->user_id !== $user->id;
    }
}
