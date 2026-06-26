<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use App\Http\Requests\Servicios\Concerns\ValidaOrigenPago;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePagoRequest extends FormRequest
{
    use AutorizaPermiso, ValidaOrigenPago;

    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::EDITAR_PAGOS);
    }

    public function rules(): array
    {
        return $this->reglasOrigenPago();
    }

    public function messages(): array
    {
        return $this->mensajesOrigenPago();
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
        ];
    }
}
