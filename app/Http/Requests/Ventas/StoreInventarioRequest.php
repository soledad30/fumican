<?php

namespace App\Http\Requests\Ventas;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class StoreInventarioRequest extends FormRequest
{
    use AutorizaPermiso;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::CREAR_INVENTARIO);
    }

    public function rules(): array
    {
        return [
            'stock' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ];
    }
}
