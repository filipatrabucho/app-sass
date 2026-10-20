<?php

namespace App\Core\Employees\Http\Controllers;

use App\Core\Employees\Models\Employee;
use App\Core\Files\FileStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AvatarController
{
    /**
     * Qualquer utilizador autenticado do tenant vê fotografias (diretório interno da empresa).
     */
    public function show(Employee $employee, FileStorage $files): StreamedResponse
    {
        abort_unless($files->exists($employee->avatar_path), 404);

        return $files->response($employee->avatar_path);
    }

    public static function store(Request $request, Employee $employee, FileStorage $files): Employee
    {
        $request->validate(['avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']]);

        $files->remove($employee->avatar_path);
        $path = $files->storeAs($request->file('avatar'), 'avatars', "employee-{$employee->id}-".now()->timestamp);
        $employee->update(['avatar_path' => $path]);

        return $employee;
    }
}
