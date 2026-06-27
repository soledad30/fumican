<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use App\Models\Servicios\ConsultaMedica;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompletarRegistroLlegadaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermisoEnum::EDITAR_CONSULTAS_MEDICAS->value);
    }

    public function rules(): array
    {
        $consulta = ConsultaMedica::with('mascota.propietario')->find($this->route('id'));
        $clienteId = $consulta?->mascota?->propietario?->id;

        return [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'ci' => [
                'required',
                'string',
                'max:15',
                Rule::unique('clientes', 'ci')->ignore($clienteId),
            ],
            'phone_number' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['required', 'integer', 'in:0,1,2'],
            'address' => ['nullable', 'string', 'max:255'],
            'pet_name' => ['required', 'string', 'max:50'],
            'pet_color' => ['required', 'string', 'max:50'],
            'pet_gender' => ['nullable', 'string', 'max:20'],
            'pet_age' => ['nullable', 'integer', 'min:0', 'max:50'],
            'pet_weight' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'breed_id' => ['nullable', 'exists:razas,id'],
            'specie' => ['nullable', 'string', 'max:80'],
            'breed' => ['nullable', 'string', 'max:80'],
            'emergencia' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'El nombre del propietario es obligatorio.',
            'last_name.required' => 'El apellido del propietario es obligatorio.',
            'ci.required' => 'El CI del propietario es obligatorio.',
            'ci.unique' => 'Ese CI ya está registrado en otro cliente.',
            'phone_number.required' => 'El teléfono es obligatorio.',
            'pet_color.required' => 'El color de la mascota es obligatorio.',
            'pet_name.required' => 'El nombre de la mascota es obligatorio.',
        ];
    }
}
