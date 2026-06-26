<?php

namespace App\Http\Requests\Servicios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class RecepcionUpdateMascotaRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::CREAR_CLIENTES)
            || $this->usuarioPuede(PermisoEnum::EDITAR_MASCOTAS);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'color' => 'required|string|max:50',
            'age' => 'nullable|integer|min:0|max:50',
            'photo' => 'nullable|image|max:5120',
            'breed_id' => 'required|exists:razas,id',
            'customer_id' => 'required|exists:clientes,id',
        ];
    }
}
