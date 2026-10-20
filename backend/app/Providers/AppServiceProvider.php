<?php

namespace App\Providers;

use App\Core\Auth\Models\User;
use App\Core\Departments\Models\Department;
use App\Core\Departments\Models\Position;
use App\Core\Employees\Models\Employee;
use App\Core\Employees\Policies\EmployeePolicy;
use App\Core\Holidays\Models\Holiday;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

        // Nomes estáveis nos campos polimórficos (audit_logs, attachments, notifications).
        // Os módulos acrescentam os seus no respetivo service provider.
        Relation::morphMap([
            'user' => User::class,
            'employee' => Employee::class,
            'department' => Department::class,
            'position' => Position::class,
            'holiday' => Holiday::class,
        ]);

        Gate::policy(Employee::class, EmployeePolicy::class);

        // O role admin tem tudo dentro do tenant, incluindo permissões futuras.
        Gate::before(fn ($user) => $user instanceof User && $user->hasRole('admin') ? true : null);

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
