<?php

namespace App\Http\Requests\Usuarios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRolRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (
            $user->esRolConAccesoTotal()
            || $user->tienePermisoBd(config('permisos-bd.admin'))
            || $this->usuarioPuede(PermisoEnum::EDITAR_ROLES)
        );
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['integer', 'exists:permisos,id'],
        ];
    }
}
