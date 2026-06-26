<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class UpdateServicioRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::EDITAR_SERVICIOS);
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:500',
            'precio' => 'required|numeric|min:0',
            'esta_activo' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del servicio es obligatorio.',
            'precio.required' => 'El precio es obligatorio.',
            'precio.min' => 'El precio no puede ser negativo.',
        ];
    }
}
