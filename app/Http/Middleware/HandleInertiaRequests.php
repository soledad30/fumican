<?php

namespace App\Http\Middleware;

use App\Services\Auditoria\MenuService;
use App\Services\Auditoria\VisitaService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        if ($request->user()) {
            $request->user()->loadMissing('rol.permisos');
        }

        $permissions = $request->user()
            ? $request->user()->getAllPermissions()
            : null;

        $visitCount = 0;
        if ($request->isMethod('GET')) {
            try {
                $visitCount = app(VisitaService::class)->registrarVisita('/'.$request->path());
            } catch (\Throwable) {
                $visitCount = 0;
            }
        }

        return array_merge(parent::share($request), [
            'auth.user_permissions' => $permissions,
            'auth.permisos_nombres' => $request->user()
                ? $request->user()->getPermisosBd()->pluck('nombre')->values()
                : [],
            'auth.permisos_mapa' => config('permisos-bd.mapa'),
            'auth.user_menus' => $request->user()
                ? app(MenuService::class)->treeFor($request->user())
                : null,
            'visitCount' => $visitCount,
            'visitasTotal' => $this->visitasTotalSeguras(),
        ]);
    }

    private function visitasTotalSeguras(): int
    {
        try {
            return app(VisitaService::class)->getTotalSitio();
        } catch (\Throwable) {
            return 0;
        }
    }
}
