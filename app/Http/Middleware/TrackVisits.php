<?php

namespace App\Http\Middleware;

use App\Services\Auditoria\BitacoraService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackVisits
{
    public function __construct(protected BitacoraService $bitacoraService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $request->user() && $request->header('X-Inertia')) {
            try {
                $this->bitacoraService->accesoRecurso('/'.$request->path(), $request->route()?->getName());
            } catch (\Throwable) {
                //
            }
        }

        return $response;
    }
}
