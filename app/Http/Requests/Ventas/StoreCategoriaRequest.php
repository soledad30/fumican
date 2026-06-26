<?php

namespace App\Http\Requests\Ventas;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoriaRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $this->usuarioPuede(PermisoEnum::EDITAR_CATEGORIAS);
        }

        return $this->usuarioPuede(PermisoEnum::CREAR_CATEGORIAS);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre de la categoría es obligatorio.',
            'name.string' => 'El nombre debe ser un texto válido.',
            'name.max' => 'El nombre no debe superar los 100 caracteres.',
        ];
    }

    public function validated($key = null, $default = null): array
    {
        parent::validated($key, $default);

        return ['nombre' => $this->input('name')];
    }
}
