<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Restringe una ruta a uno o más perfiles: ->middleware('perfil:DIRECTOR,ENCARGADO'). */
class EnsurePerfil
{
    public function handle(Request $request, Closure $next, string ...$perfiles): Response
    {
        $usuario = $request->user();
        abort_unless($usuario && $usuario->tienePerfil(...$perfiles), 403, 'Su perfil no tiene permiso para esta opción.');

        return $next($request);
    }
}
