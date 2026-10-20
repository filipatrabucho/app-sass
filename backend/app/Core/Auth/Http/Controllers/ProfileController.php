<?php

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Http\Resources\MeResource;
use App\Core\Employees\Http\Controllers\AvatarController;
use App\Core\Employees\Http\Requests\UpdateProfileRequest;
use App\Core\Files\FileStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Perfil do próprio utilizador (portal do colaborador).
 */
class ProfileController
{
    public function update(UpdateProfileRequest $request): MeResource
    {
        $user = $request->user();
        $data = $request->validated();

        if (array_key_exists('locale', $data)) {
            $user->update(['locale' => $data['locale']]);
        }

        $user->employee?->update(Arr::except($data, ['locale']));

        return new MeResource($user->refresh());
    }

    public function avatar(Request $request, FileStorage $files): MeResource
    {
        $employee = $request->user()->employee;
        abort_if($employee === null, 422, __('employees.no_employee'));

        AvatarController::store($request, $employee, $files);

        return new MeResource($request->user()->refresh());
    }
}
