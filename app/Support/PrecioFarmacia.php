<?php

namespace App\Support;

class PrecioFarmacia
{
    /**
     * Calcula precio de venta: costo + margen + IVA.
     *
     * @return array{precio_base: float, precio_venta: float, margen: float, iva: float}
     */
    public static function calcularVenta(float $precioCompra, ?float $margen = null, ?float $iva = null): array
    {
        $margen = $margen ?? (float) config('ventas.margen_default', 0.30);
        $iva = $iva ?? (float) config('ventas.iva_porcentaje', 0.13);

        $precioBase = round($precioCompra * (1 + $margen), 2);
        $precioVenta = round($precioBase * (1 + $iva), 2);

        return [
            'precio_base' => $precioBase,
            'precio_venta' => $precioVenta,
            'margen' => $margen,
            'iva' => $iva,
        ];
    }
}
