<?php

namespace App\Http\Requests\Ventas;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class StoreAlmacenRequest extends FormRequest
{
    use AutorizaPermiso;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::CREAR_ALMACENES);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'], // Obligatorio, texto, máximo 255 caracteres
            'location' => ['required', 'string', 'max:255'], // Obligatorio, texto, máximo 255 caracteres
            'description' => ['nullable', 'string'], // Opcional, texto
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
            'name.required' => 'El nombre del almacén es obligatorio.',
            'name.string' => 'El nombre del almacén debe ser un texto válido.',
            'name.max' => 'El nombre del almacén no debe exceder los 255 caracteres.',

            'location.required' => 'La ubicación del almacén es obligatoria.',
            'location.string' => 'La ubicación del almacén debe ser un texto válido.',
            'location.max' => 'La ubicación del almacén no debe exceder los 255 caracteres.',

            'description.string' => 'La descripción debe ser un texto válido.',
        ];
    }
}
