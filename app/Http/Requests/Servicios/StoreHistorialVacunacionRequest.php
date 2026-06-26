<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class StoreHistorialVacunacionRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::CREAR_VACUNAS);
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
            'mascota_id.exists' => 'La mascota no existe.',
            'vacuna_id.required' => 'Debe seleccionar una vacuna.',
            'vacuna_id.exists' => 'La vacuna no existe.',
            'fecha_aplicacion.required' => 'La fecha de aplicación es obligatoria.',
            'fecha_proxima.after_or_equal' => 'La próxima dosis debe ser posterior o igual a la aplicación.',
        ];
    }
}
