<?php

namespace App\Services\Auditoria;

use App\Models\Auditoria\Bitacora;
use App\Support\BitacoraDescripciones;
use Illuminate\Support\Facades\Request;

class BitacoraService
{
    public function registrar(
        string $accion,
        string $modulo,
        ?string $descripcion = null,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null,
        ?int $usuarioId = null
    ): void {
        Bitacora::create([
            'usuario_id' => $usuarioId ?? auth()->id(),
            'accion' => $accion,
            'modulo' => $modulo,
            'descripcion' => $descripcion,
            'ip' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
            'creado_en' => now(),
        ]);
    }

    public function loginExitoso(int $usuarioId, string $email): void
    {
        $this->registrar('login_exitoso', 'autenticacion', "Inicio de sesión: {$email}", null, null, $usuarioId);
    }

    public function loginFallido(string $email): void
    {
        $this->registrar('login_fallido', 'autenticacion', "Intento fallido: {$email}");
    }

    public function accesoRecurso(string $ruta, ?string $nombreRuta = null): void
    {
        $this->registrar('acceso', 'navegacion', $nombreRuta ?? $ruta);
    }

    public function getRecientes(int $limit = 50)
    {
        return Bitacora::orderByDesc('creado_en')->limit($limit)->get();
    }

    public function getEstadisticasAcceso(): array
    {
        $loginsOk = Bitacora::where('accion', 'login_exitoso')->count();
        $loginsFail = Bitacora::where('accion', 'login_fallido')->count();

        $recursos = Bitacora::where('accion', 'acceso')
            ->selectRaw('modulo, descripcion, COUNT(*) as total')
            ->groupBy('modulo', 'descripcion')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return [
            'logins_exitosos' => $loginsOk,
            'logins_fallidos' => $loginsFail,
            'recursos_mas_accedidos' => $recursos,
        ];
    }

    public function buscar(array $filters = [], int $perPage = 30)
    {
        $query = Bitacora::query()
            ->with('usuario:id,nombre,email')
            ->orderByDesc('creado_en');

        if (! empty($filters['accion'])) {
            $query->where('accion', $filters['accion']);
        }

        if (! empty($filters['modulo'])) {
            $query->where('modulo', 'like', '%'.$filters['modulo'].'%');
        }

        if (! empty($filters['search_term'])) {
            $term = $filters['search_term'];
            $query->where(function ($q) use ($term) {
                $q->where('descripcion', 'like', "%{$term}%")
                    ->orWhere('ip', 'like', "%{$term}%")
                    ->orWhere('accion', 'like', "%{$term}%")
                    ->orWhere('modulo', 'like', "%{$term}%")
                    ->orWhereHas('usuario', function ($uq) use ($term) {
                        $uq->where('nombre', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    });
            });
        }

        if (! empty($filters['fecha_desde'])) {
            $query->whereDate('creado_en', '>=', $filters['fecha_desde']);
        }

        if (! empty($filters['fecha_hasta'])) {
            $query->whereDate('creado_en', '<=', $filters['fecha_hasta']);
        }

        return $query
            ->paginate($perPage)
            ->through(fn (Bitacora $registro) => $this->formatearRegistro($registro))
            ->withQueryString();
    }

    private function formatearRegistro(Bitacora $registro): array
    {
        return [
            'id' => $registro->id,
            'creado_en' => $registro->creado_en?->format('d/m/Y H:i:s'),
            'accion' => $registro->accion,
            'accion_label' => BitacoraDescripciones::accionLabel($registro->accion),
            'modulo' => $registro->modulo,
            'modulo_label' => BitacoraDescripciones::moduloLabel($registro->modulo),
            'descripcion' => $registro->descripcion,
            'usuario' => $registro->usuario?->nombre ?? 'Sistema',
            'usuario_email' => $registro->usuario?->email,
            'ip' => $registro->ip,
        ];
    }

    public function getAccionesDisponibles(): array
    {
        return Bitacora::query()
            ->select('accion')
            ->distinct()
            ->orderBy('accion')
            ->pluck('accion')
            ->all();
    }

    public function getModulosDisponibles(): array
    {
        return Bitacora::query()
            ->select('modulo')
            ->distinct()
            ->orderBy('modulo')
            ->pluck('modulo')
            ->all();
    }
}
