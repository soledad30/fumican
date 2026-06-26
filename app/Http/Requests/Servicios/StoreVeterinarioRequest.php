<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class StoreVeterinarioRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::CREAR_CONSULTAS_MEDICAS);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'ci' => ['nullable', 'string', 'max:20'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'is_specialist' => ['sometimes', 'boolean'],
            'specialty' => ['nullable', 'required_if:is_specialist,true', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'El nombre es obligatorio.',
            'email.email' => 'Ingrese un correo electronico valido (ej: nombre@clinica.com).',
            'specialty.required_if' => 'Indique la especialidad si es especialista.',
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'email' => 'correo',
            'phone_number' => 'telefono',
            'license_number' => 'licencia',
            'specialty' => 'especialidad',
        ];
    }
}
