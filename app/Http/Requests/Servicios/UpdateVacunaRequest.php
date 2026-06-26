<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVacunaRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::EDITAR_VACUNAS);
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:100',
            'duracion_dias' => 'nullable|integer|min:1|max:3650',
            'notas' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la vacuna es obligatorio.',
        ];
    }
}
