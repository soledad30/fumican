<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHistorialVacunacionRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::EDITAR_VACUNAS);
    }

    public function rules(): array
    {
        return [
            'mascota_id' => 'required|integer|exists:mascotas,id',
            'vacuna_id' => 'required|integer|exists:vacunas,id',
            'fecha_aplicacion' => 'required|date',
            'fecha_proxima' => 'nullable|date|after_or_equal:fecha_aplicacion',
            'notas' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'mascota_id.required' => 'Debe indicar la mascota.',
            'vacuna_id.required' => 'Debe seleccionar una vacuna.',
            'fecha_aplicacion.required' => 'La fecha de aplicación es obligatoria.',
        ];
    }
}
