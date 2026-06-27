<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use Illuminate\Foundation\Http\FormRequest;

class IniciarAtencionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermisoEnum::EDITAR_CONSULTAS_MEDICAS->value);
    }

    public function rules(): array
    {
        return [
            'pet_id' => ['required', 'exists:mascotas,id'],
            'service_id' => ['nullable', 'exists:servicios,id'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
