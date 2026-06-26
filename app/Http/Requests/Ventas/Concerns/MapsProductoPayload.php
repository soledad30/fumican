<?php

namespace App\Http\Requests\Ventas\Concerns;

trait MapsProductoPayload
{
    protected function productoPayload(): array
    {
        return [
            'nombre' => $this->input('name'),
            'unidad_medida' => $this->input('unit', 'unidad'),
            'presentacion' => $this->input('presentation'),
            'dosificacion' => $this->filled('dosage') ? $this->input('dosage') : null,
            'fabricante' => $this->input('manufacturer'),
            'fecha_vencimiento' => $this->input('expiration_date'),
            'sustancia_controlada' => $this->input('controlled_substance') === 'yes',
            'categoria_id' => $this->input('category_id'),
            'stock_minimo' => (int) $this->input('min_stock', 0),
        ];
    }
}
