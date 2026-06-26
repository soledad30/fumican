<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class StoreRazaRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::CREAR_MASCOTAS);
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if ($this->has('name') && ! $this->has('nombre')) {
            $merge['nombre'] = $this->input('name');
        }
        if ($this->has('specie_id') && ! $this->has('especie_id')) {
            $merge['especie_id'] = $this->input('specie_id');
        }
        if ($merge) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:100',
            'especie_id' => 'required|integer|exists:especies,id',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la raza es obligatorio.',
            'especie_id.required' => 'Debe seleccionar una especie.',
            'especie_id.exists' => 'La especie seleccionada no existe.',
        ];
    }
}
