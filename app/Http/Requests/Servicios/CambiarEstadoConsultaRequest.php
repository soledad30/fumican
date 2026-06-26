<?php

namespace App\Http\Requests\Servicios;

use App\Enums\EstadoConsultaEnum;
use App\Enums\PermisoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEstadoConsultaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermisoEnum::EDITAR_CONSULTAS_MEDICAS->value);
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in(EstadoConsultaEnum::values())],
            'emergencia' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'estado.required' => 'Debe indicar el nuevo estado.',
            'estado.in' => 'Estado de consulta inválido.',
        ];
    }
}
