<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use Illuminate\Foundation\Http\FormRequest;

class ReprogramarConsultaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermisoEnum::EDITAR_CONSULTAS_MEDICAS->value);
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date', 'after_or_equal:today'],
            'hora' => ['required', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'La nueva fecha es obligatoria.',
            'fecha.after_or_equal' => 'La nueva fecha no puede ser anterior a hoy.',
            'hora.required' => 'El nuevo horario es obligatorio.',
        ];
    }
}
