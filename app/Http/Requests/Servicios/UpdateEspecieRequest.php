<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEspecieRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::EDITAR_MASCOTAS);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name') && ! $this->has('nombre')) {
            $this->merge(['nombre' => $this->input('name')]);
        }
    }

    public function rules(): array
    {
        $id = $this->route('especy') ?? $this->route('especie') ?? $this->route('id');

        return [
            'nombre' => ['required', 'string', 'max:100', Rule::unique('especies', 'nombre')->ignore($id)],
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
