<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class StoreServicioRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::CREAR_SERVICIOS);
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
            'nombre.max' => 'El nombre no debe exceder 100 caracteres.',
            'precio.required' => 'El precio es obligatorio.',
            'precio.min' => 'El precio no puede ser negativo.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'descripcion' => 'descripción',
            'precio' => 'precio',
            'esta_activo' => 'activo',
        ];
    }
}
