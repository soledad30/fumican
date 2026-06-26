<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'No autorizado.');
        }

        if ($user->esRolConAccesoTotal()
            || $user->tienePermisoBd(config('permisos-bd.admin'))
            || $user->tienePermisoBd($permiso)) {
            return $next($request);
        }

        abort(403, 'No tiene permiso para acceder a este recurso.');
    }
}
