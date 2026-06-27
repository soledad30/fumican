<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use App\Http\Requests\Servicios\Concerns\ValidaOrigenPago;
use Illuminate\Foundation\Http\FormRequest;

class StorePagoRequest extends FormRequest
{
    use AutorizaPermiso, ValidaOrigenPago;

    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::CREAR_PAGOS);
    }

    public function rules(): array
    {
        return array_merge($this->reglasOrigenPago(), [
            'num_cuotas' => 'nullable|integer|min:1|max:36',
            'cuotas_plan' => 'nullable|array|min:1|max:36',
            'cuotas_plan.*.monto' => 'required_with:cuotas_plan|numeric|min:0.01',
            'cuotas_plan.*.fecha' => 'required_with:cuotas_plan|date',
        ]);
    }

    public function messages(): array
    {
        return array_merge($this->mensajesOrigenPago(), [
            'num_cuotas.min' => 'Debe haber al menos una cuota.',
            'num_cuotas.max' => 'Máximo 36 cuotas permitidas.',
        ]);
    }

    public function attributes(): array
    {
        return [
            'nota_venta_id' => 'nota de venta',
            'consulta_id' => 'consulta médica',
            'monto' => 'monto',
            'tipo_pago' => 'tipo de pago',
            'metodo_pago' => 'método de pago',
            'id_transaccion_externa' => 'ID de transacción',
            'fecha_pago' => 'fecha de pago',
            'num_cuotas' => 'número de cuotas',
        ];
    }
}
