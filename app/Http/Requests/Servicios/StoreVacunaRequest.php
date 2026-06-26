<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class StoreVacunaRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::CREAR_VACUNAS);
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
            'duracion_dias.min' => 'La duración debe ser al menos 1 día.',
            'duracion_dias.max' => 'La duración no puede superar 10 años.',
        ];
    }
}
