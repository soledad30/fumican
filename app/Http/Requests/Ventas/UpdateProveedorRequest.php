<?php

namespace App\Http\Requests\Ventas;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProveedorRequest extends FormRequest
{
    use AutorizaPermiso;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::EDITAR_PROVEEDORES);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $supplierId = $this->route('id'); // Obtener el ID de la ruta

        return [
            'name' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'phoneNumber' => 'required|string|max:15|regex:/^[0-9]+$/',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('proveedores', 'email')->ignore($supplierId)
            ],
            'address' => 'required|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del proveedor es obligatorio.',
            'name.string' => 'El nombre debe ser un texto válido.',
            'name.max' => 'El nombre no debe superar los 255 caracteres.',

            'country.required' => 'El país es obligatorio.',
            'country.string' => 'El país debe ser un texto válido.',
            'country.max' => 'El país no debe superar los 255 caracteres.',

            'phoneNumber.required' => 'El número de teléfono es obligatorio.',
            'phoneNumber.string' => 'El número de teléfono debe ser un texto válido.',
            'phoneNumber.max' => 'El número de teléfono no debe superar los 15 dígitos.',
            'phoneNumber.regex' => 'El número de teléfono solo puede contener dígitos.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'email.max' => 'El correo electrónico no debe superar los 255 caracteres.',
            'email.unique' => 'El correo electrónico ya está registrado.',

            'address.required' => 'La dirección es obligatoria.',
            'address.string' => 'La dirección debe ser un texto válido.',
            'address.max' => 'La dirección no debe superar los 255 caracteres.',
        ];
    }
}
