<?php

namespace App\Http\Requests\Ventas;

use App\Enums\PermisoEnum;
use App\Enums\UnidadMedidaEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use App\Http\Requests\Ventas\Concerns\MapsProductoPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductoRequest extends FormRequest
{
    use AutorizaPermiso, MapsProductoPayload;

    public function authorize(): bool
    {
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $this->usuarioPuede(PermisoEnum::EDITAR_MEDICAMENTOS);
        }

        return $this->usuarioPuede(PermisoEnum::CREAR_MEDICAMENTOS);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $unidadesConDosificacion = ['comprimido', 'capsula', 'ml', 'ampolla', 'tableta'];

        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'unit' => ['required', Rule::in(UnidadMedidaEnum::values())],
            'presentation' => ['nullable', 'string', 'max:120'],
            'dosage' => [
                Rule::requiredIf(fn () => in_array($this->input('unit'), $unidadesConDosificacion, true)),
                'nullable',
                'string',
                'max:100',
            ],
            'manufacturer' => ['required', 'string', 'max:255'],
            'expiration_date' => ['nullable', 'date', 'after:today'],
            'controlled_substance' => ['required', 'in:yes,no'],
            'category_id' => ['required', 'exists:categorias,id'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
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
            'name.required' => 'El nombre del medicamento es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',
            'dosage.required' => 'La dosificación es obligatoria para comprimidos, cápsulas, ml, ampollas y tabletas.',
            'dosage.max' => 'La dosificación no debe exceder los 100 caracteres.',
            'manufacturer.required' => 'El fabricante es obligatorio.',
            'manufacturer.max' => 'El nombre del fabricante no debe exceder los 255 caracteres.',
            'expiration_date.required' => 'La fecha de expiración es obligatoria.',
            'expiration_date.date' => 'La fecha de expiración debe ser una fecha válida.',
            'expiration_date.after' => 'La fecha de expiración debe ser una fecha futura.',
            'controlled_substance.required' => 'Debes indicar si es una sustancia controlada.',
            'controlled_substance.in' => 'El valor para sustancia controlada debe ser "sí" o "no".',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no es válida.',
        ];
    }

    public function validated($key = null, $default = null): array
    {
        parent::validated($key, $default);

        return $this->productoPayload();
    }
}
