<?php

namespace App\Http\Middleware;

use App\Services\Auditoria\BitacoraService;
use App\Support\BitacoraDescripciones;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrarAccionesBitacora
{
    private const RUTAS_IGNORADAS = [
        'login',
        'logout',
        'password.',
        'two-factor.',
        'sanctum.',
        'livewire.',
    ];

    public function __construct(protected BitacoraService $bitacoraService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->debeRegistrar($request, $response)) {
            return $response;
        }

        try {
            $datos = BitacoraDescripciones::inferirDesdeRequest($request);
            if ($datos !== null) {
                $this->bitacoraService->registrar(
                    $datos['accion'],
                    $datos['modulo'],
                    $datos['descripcion']
                );
            }
        } catch (\Throwable) {
            //
        }

        return $response;
    }

    private function debeRegistrar(Request $request, Response $response): bool
    {
        if (! $request->user()) {
            return false;
        }

        if ($response->getStatusCode() >= 400) {
            return false;
        }

        if ($request->isMethod('GET')) {
            return false;
        }

        $routeName = $request->route()?->getName() ?? '';
        foreach (self::RUTAS_IGNORADAS as $prefijo) {
            if (str_starts_with($routeName, $prefijo)) {
                return false;
            }
        }

        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }
}
