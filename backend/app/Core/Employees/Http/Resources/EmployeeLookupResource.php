<?php

namespace App\Core\Employees\Http\Resources;

use App\Core\Employees\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forma mínima para selects (EmployeeSelect).
 *
 * @mixin Employee
 */
class EmployeeLookupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'department' => $this->department?->name,
        ];
    }
}
