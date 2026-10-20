<?php

namespace App\Core\Employees\Http\Resources;

use App\Core\Employees\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Employee
 */
class EmployeeResource extends JsonResource
{
    /** Incluir dados sensíveis (IBAN, NIF, morada) — só para RH ou para o próprio. */
    private bool $sensitive = false;

    public function withSensitive(bool $sensitive = true): static
    {
        $this->sensitive = $sensitive;

        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_number' => $this->employee_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status,
            'hire_date' => $this->hire_date?->toDateString(),
            'avatar_url' => $this->avatar_path ? url("/api/employees/{$this->id}/avatar?v=".$this->updated_at?->timestamp) : null,
            'department' => $this->whenLoaded('department', fn () => $this->department ? ['id' => $this->department->id, 'name' => $this->department->name] : null),
            'position' => $this->whenLoaded('position', fn () => $this->position ? ['id' => $this->position->id, 'name' => $this->position->name] : null),
            'manager' => $this->whenLoaded('manager', fn () => $this->manager ? ['id' => $this->manager->id, 'full_name' => $this->manager->full_name] : null),
            'department_id' => $this->department_id,
            'position_id' => $this->position_id,
            'manager_id' => $this->manager_id,
            'subordinates_count' => $this->whenCounted('subordinates'),
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'active' => $this->user->active,
                'roles' => $this->user->getRoleNames()->values(),
                'last_login_at' => $this->user->last_login_at?->toIso8601String(),
            ] : null),
            $this->mergeWhen($this->sensitive, fn () => [
                'nif' => $this->nif,
                'birth_date' => $this->birth_date?->toDateString(),
                'address' => $this->address,
                'iban' => $this->iban,
                'external_id' => $this->external_id,
            ]),
        ];
    }
}
