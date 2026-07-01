<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::CREAR_CLIENTES);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'ci' => ['required', 'string', 'max:15', 'unique:clientes,ci'],
            'phone_number' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['required', 'integer', 'in:0,1,2'],
            'birthdate' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('gender') && $this->gender !== null && $this->gender !== '') {
            $merge['gender'] = (int) $this->gender;
        }

        if ($this->birthdate === '') {
            $merge['birthdate'] = null;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'El campo nombre es requerido.',
            'first_name.string' => 'El campo nombre debe ser una cadena de texto.',
            'first_name.max' => 'El campo nombre no debe exceder los 50 caracteres.',
            'last_name.required' => 'El campo apellido es requerido.',
            'last_name.string' => 'El campo apellido debe ser una cadena de texto.',
            'last_name.max' => 'El campo apellido no debe exceder los 50 caracteres.',
            'ci.required' => 'El campo CI es requerido.',
            'ci.string' => 'El campo CI debe ser una cadena de texto.',
            'ci.max' => 'El campo CI no debe exceder los 15 caracteres.',
            'phone_number.required' => 'El campo número de teléfono es requerido.',
            'phone_number.string' => 'El campo número de teléfono debe ser una cadena de texto.',
            'phone_number.max' => 'El campo número de teléfono no debe exceder los 20 caracteres.',
            'gender.required' => 'El campo género es requerido.',
            'gender.integer' => 'El campo género debe ser un número entero.',
            'gender.in' => 'El campo género debe ser uno de los siguientes valores: Masculino, Femenino, Otro.',
            'birthdate.required' => 'El campo fecha de nacimiento es requerido.',
            'birthdate.date' => 'El campo fecha de nacimiento debe ser una fecha válida.',
            'address.max' => 'La dirección no debe exceder los 255 caracteres.',
        ];
    }
}
