<?php

namespace App\Http\Requests\Usuarios;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class DestroyRolRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && (
            $user->esRolConAccesoTotal()
            || $user->tienePermisoBd(config('permisos-bd.admin'))
            || $this->usuarioPuede(PermisoEnum::ELIMINAR_ROLES)
        );
    }

    public function rules(): array
    {
        return [];
    }
}
