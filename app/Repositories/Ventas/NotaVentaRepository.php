<?php

namespace App\Repositories\Ventas;

use App\Models\Ventas\NotaVenta;

class NotaVentaRepository
{
    public function getAll()
    {
        return NotaVenta::with([
            'almacen:id,nombre,ubicacion,descripcion',
            'cliente:id,nombre,apellido',
        ])
            ->orderByDesc('fecha_venta')
            ->orderByDesc('creado_en')
            ->paginate(15);
    }

    public function findById($id)
    {
        return NotaVenta::with(['almacen', 'usuario', 'cliente', 'detalles.producto'])->findOrFail($id);
    }

    public function create(array $salesNoteData)
    {
        return NotaVenta::create($salesNoteData);
    }

    public function update($id, array $data)
    {
        $nota = NotaVenta::findOrFail($id);
        $nota->update($data);

        return $nota;
    }
}
