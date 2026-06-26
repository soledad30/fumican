<?php

namespace App\Http\Requests\Portal;

use App\Enums\PermisoEnum;
use App\Enums\RolEnum;
use App\Support\PermisoBd;
use Illuminate\Foundation\Http\FormRequest;

class StorePortalReservaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($user->rol?->nombre === RolEnum::CLIENTE->value) {
            return true;
        }

        return $user->can(PermisoEnum::RESERVAR_CITAS->value)
            || $user->can(PermisoEnum::CREAR_CONSULTAS_MEDICAS->value)
            || $user->tienePermisoBd(PermisoBd::resolver(PermisoEnum::RESERVAR_CITAS->value));
    }

    public function rules(): array
    {
        return [
            'mascota_id' => 'required|integer|exists:mascotas,id',
            'servicio_id' => 'required|integer|exists:servicios,id',
            'fecha' => 'required|date|after_or_equal:today',
            'hora' => 'required|string|max:10',
            'comentario' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'mascota_id.required' => 'Seleccione la mascota.',
            'servicio_id.required' => 'Seleccione el tipo de servicio.',
            'fecha.required' => 'Indique la fecha de la cita.',
            'fecha.after_or_equal' => 'La fecha debe ser hoy o posterior.',
            'hora.required' => 'Seleccione el horario.',
        ];
    }
}
