<?php

namespace App\Http\Middleware;

use App\Services\Auditoria\BitacoraService;
use App\Support\BitacoraDescripciones;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVisits
{
    public function __construct(protected BitacoraService $bitacoraService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $request->user() && $request->header('X-Inertia') && $response->isSuccessful()) {
            try {
                $descripcion = BitacoraDescripciones::describirNavegacion(
                    $request->route()?->getName(),
                    $request->path()
                );
                $this->bitacoraService->registrar('acceso', 'navegacion', $descripcion);
            } catch (\Throwable) {
                //
            }
        }

        return $response;
    }
}
