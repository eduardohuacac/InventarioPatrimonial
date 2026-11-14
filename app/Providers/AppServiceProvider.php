<?php

namespace App\Providers;

use App\Domain\Movimientos\FabricaHandlers;
use App\Models\Bien;
use App\Policies\BienPolicy;
use App\Support\Auditor;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FabricaHandlers::class);
    }

    public function boot(): void
    {
        Gate::policy(Bien::class, BienPolicy::class);
        Paginator::defaultView('partials.paginacion');

        // Política de contraseñas (OWASP A07).
        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        // Registro de accesos en la auditoría (OWASP A09).
        Event::listen(Login::class, function (Login $e) {
            Auditor::registrar('iniciar_sesion', $e->user, [], $e->user->getAuthIdentifier());
        });
        Event::listen(Logout::class, function (Logout $e) {
            if ($e->user) {
                Auditor::registrar('cerrar_sesion', $e->user, [], $e->user->getAuthIdentifier());
            }
        });
        Event::listen(Failed::class, function (Failed $e) {
            Auditor::registrar('acceso_fallido', 'User', ['correo' => $e->credentials['email'] ?? null], $e->user?->getAuthIdentifier());
        });
    }
}
