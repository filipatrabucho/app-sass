<?php

namespace App\Central\Http\Controllers;

use App\Core\Auth\Http\Requests\LoginRequest;
use App\Core\Http\ApiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CentralAuthController
{
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::guard('central')->attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            throw new ApiException(__('auth.failed'), 'INVALID_CREDENTIALS', 422, [
                'email' => [__('auth.failed')],
            ]);
        }

        $request->session()->regenerate();

        return $this->me($request);
    }

    public function me(Request $request): JsonResponse
    {
        $admin = Auth::guard('central')->user();

        return response()->json([
            'admin' => ['id' => $admin->id, 'name' => $admin->name, 'email' => $admin->email],
        ]);
    }

    public function logout(Request $request): Response
    {
        Auth::guard('central')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }
}
