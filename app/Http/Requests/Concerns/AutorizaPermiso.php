<?php

namespace App\Http\Requests\Concerns;

use App\Enums\PermisoEnum;

trait AutorizaPermiso
{
    protected function usuarioPuede(PermisoEnum|string $permiso): bool
    {
        if (! $this->user()) {
            return false;
        }

        $valor = $permiso instanceof PermisoEnum ? $permiso->value : $permiso;

        return $this->user()->can($valor);
    }
}
