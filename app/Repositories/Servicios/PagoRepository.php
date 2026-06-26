<?php

namespace App\Repositories\Servicios;

use App\Models\Servicios\Pago;

class PagoRepository
{
    public function getAll()
    {
        return Pago::with(['notaVenta.cliente', 'consulta.mascota', 'consulta.servicio', 'servicio', 'cliente', 'usuario'])
            ->orderByDesc('fecha_pago')
            ->paginate(10);
    }

    public function findById(int $id): Pago
    {
        return Pago::with(['notaVenta.cliente', 'consulta.mascota', 'consulta.servicio', 'servicio', 'cliente', 'usuario'])->findOrFail($id);
    }

    public function create(array $data): Pago
    {
        return Pago::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return Pago::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return Pago::destroy($id) > 0;
    }

    public function search(array $filters)
    {
        $query = Pago::with(['notaVenta.cliente', 'consulta.mascota', 'consulta.servicio', 'servicio', 'cliente', 'usuario']);

        if (! empty($filters['tipo_pago'])) {
            $query->where('tipo_pago', $filters['tipo_pago']);
        }

        if (! empty($filters['search_term'])) {
            $term = $filters['search_term'];
            $likeOp = '%'.$term.'%';
            $query->where(function ($q) use ($term, $likeOp) {
                $q->where('id_transaccion_externa', 'like', $likeOp)
                    ->orWhereHas('notaVenta.cliente', fn ($c) => $c->where('nombre', 'like', $likeOp)
                        ->orWhere('apellido', 'like', $likeOp))
                    ->orWhereHas('consulta.mascota', fn ($m) => $m->where('nombre', 'like', $likeOp))
                    ->orWhereHas('servicio', fn ($s) => $s->where('nombre', 'like', $likeOp));
                if (is_numeric($term)) {
                    $q->orWhere('id', (int) $term);
                }
            });
        }

        return $query->orderByDesc('fecha_pago')->paginate(10);
    }

    public function estadisticas(): array
    {
        return [
            'total_contado' => (float) Pago::where('tipo_pago', 'contado')->sum('monto'),
            'total_credito' => (float) Pago::where('tipo_pago', 'credito')->sum('monto'),
            'pendientes_credito' => Pago::where('tipo_pago', 'credito')->whereNull('fecha_pago')->count(),
        ];
    }
}
