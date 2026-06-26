<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class StoreMascotaRequest extends FormRequest
{
    use AutorizaPermiso;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::CREAR_MASCOTAS);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'color' => 'required|string|max:50',
            'age' => 'nullable|integer|min:0|max:50',
            'photo' => 'nullable|image|max:5120',
            'photo_url' => 'nullable|string|max:255',
            'breed_id' => 'required|exists:razas,id',
            'customer_id' => 'required|exists:clientes,id',
        ];
    }
}
