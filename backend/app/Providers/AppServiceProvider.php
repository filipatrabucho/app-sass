<?php

namespace App\Providers;

use App\Core\Auth\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->getHost().'|'.strtolower((string) $request->input('email')).'|'.$request->ip());
        });

        // O link de reposição aponta para o SPA no domínio do tenant atual.
        ResetPassword::createUrlUsing(function (User $user, string $token) {
            $domain = tenant()?->primaryDomain() ?? request()->getHost();
            $port = config('platform.frontend_port');

            return sprintf(
                '%s://%s%s/reset-password?token=%s&email=%s',
                config('platform.frontend_scheme'),
                $domain,
                $port ? ':'.$port : '',
                $token,
                urlencode($user->email),
            );
        });
    }
}
