<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class StoreEspecieRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::CREAR_MASCOTAS);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name') && ! $this->has('nombre')) {
            $this->merge(['nombre' => $this->input('name')]);
        }
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:100|unique:especies,nombre',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la especie es obligatorio.',
            'nombre.unique' => 'Esta especie ya está registrada.',
        ];
    }
}
