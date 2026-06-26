<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Models\Usuarios\Permiso;
use App\Models\Usuarios\Rol;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MatrizAccesoController extends Controller
{
    public function index(): InertiaResponse
    {
        $roles = Rol::with('permisos:id,nombre')->orderBy('nombre')->get();
        $permisos = Permiso::orderBy('nombre')->get(['id', 'nombre']);

        $matriz = $roles->map(function (Rol $rol) use ($permisos) {
            $idsPermisos = $rol->permisos->pluck('id')->all();

            return [
                'id' => $rol->id,
                'nombre' => $rol->nombre,
                'permisos' => $permisos->map(fn ($p) => [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'asignado' => in_array($p->id, $idsPermisos, true),
                ]),
            ];
        });

        return Inertia::render('Reportes/MatrizAcceso/Index', [
            'matriz' => $matriz,
            'permisos' => $permisos,
            'roles' => $roles->map(fn ($r) => ['id' => $r->id, 'nombre' => $r->nombre]),
        ]);
    }
}
