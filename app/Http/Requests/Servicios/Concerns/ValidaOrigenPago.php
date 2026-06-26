<?php

namespace App\Http\Requests\Servicios\Concerns;

trait ValidaOrigenPago
{
    protected function reglasOrigenPago(): array
    {
        return [
            'nota_venta_id' => 'nullable|integer|exists:notas_venta,id|required_without:consulta_id',
            'consulta_id' => 'nullable|integer|exists:consultas_medicas,id|required_without:nota_venta_id',
            'monto' => 'required|numeric|min:0.01',
            'tipo_pago' => 'required|in:contado,credito',
            'metodo_pago' => 'required|in:efectivo,tarjeta,transferencia,qr',
            'concepto_pago' => 'nullable|in:anticipo,saldo,completo',
            'id_transaccion_externa' => 'nullable|string|max:100',
            'fecha_pago' => 'nullable|date',
        ];
    }

    protected function mensajesOrigenPago(): array
    {
        return [
            'nota_venta_id.required_without' => 'Debe seleccionar una nota de venta o una consulta médica.',
            'nota_venta_id.exists' => 'La nota de venta no existe.',
            'consulta_id.required_without' => 'Debe seleccionar una consulta médica o una nota de venta.',
            'consulta_id.exists' => 'La consulta médica no existe.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.min' => 'El monto debe ser mayor a cero.',
            'tipo_pago.required' => 'Seleccione el tipo de pago (contado o crédito).',
            'tipo_pago.in' => 'Tipo de pago inválido.',
            'metodo_pago.required' => 'Seleccione el método de pago.',
            'metodo_pago.in' => 'Método de pago inválido.',
        ];
    }
}
