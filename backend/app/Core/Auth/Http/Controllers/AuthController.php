<?php

namespace App\Core\Auth\Http\Controllers;

use App\Core\Auth\Http\Requests\LoginRequest;
use App\Core\Http\ApiException;
use App\Core\Tenancy\Http\Middleware\EnsureSessionBelongsToTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Autenticação SPA (cookies Sanctum) dos utilizadores de tenant.
 */
class AuthController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = [...$request->only('email', 'password'), 'active' => true];

        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            throw new ApiException(__('auth.failed'), 'INVALID_CREDENTIALS', 422, [
                'email' => [__('auth.failed')],
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put(EnsureSessionBelongsToTenant::SESSION_KEY, tenant()->getTenantKey());

        $user = Auth::guard('web')->user();
        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json(['message' => __('auth.logged_in')]);
    }

    public function logout(Request $request): Response
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Resposta igual exista ou não o email (não revelar contas).
        Password::broker('users')->sendResetLink($request->only('email'));

        return response()->json(['message' => __('passwords.sent')]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker('users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw new ApiException(__($status), 'PASSWORD_RESET_FAILED', 422, ['email' => [__($status)]]);
        }

        return response()->json(['message' => __($status)]);
    }
}
