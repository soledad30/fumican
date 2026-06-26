<?php

namespace App\Traits;

use App\Enums\RolEnum;
use App\Models\Usuarios\Permiso;
use App\Support\PermisoBd;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

trait HasPermisosBd
{
    public function esRolConAccesoTotal(): bool
    {
        if (! $this->rol) {
            return false;
        }

        return in_array($this->rol->nombre, [
            RolEnum::PROPIETARIO->value,
            RolEnum::ADMINISTRADOR->value,
        ], true);
    }

    public function can($ability, $arguments = []): bool
    {
        if ($this->esRolConAccesoTotal()) {
            return true;
        }

        $nombreBd = PermisoBd::resolver($ability);

        if ($this->tienePermisoBd(config('permisos-bd.admin'))) {
            return true;
        }

        return $this->tienePermisoBd($nombreBd);
    }

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        $nombre = is_object($permission) && property_exists($permission, 'value')
            ? $permission->value
            : (string) $permission;

        return $this->can($nombre);
    }

    public function tienePermisoBd(string $nombreBd): bool
    {
        return $this->getPermisosBd()->contains('nombre', $nombreBd);
    }

    public function getAllPermissions(): SupportCollection
    {
        return $this->getPermisosBd()->map(function (Permiso $permiso) {
            $permiso->setAttribute('name', $permiso->nombre);

            return $permiso;
        });
    }

    public function getPermisosBd(): Collection
    {
        if (! $this->rol) {
            return new Collection;
        }

        if (! $this->relationLoaded('rol')) {
            $this->load('rol.permisos');
        }

        return $this->rol?->permisos ?? new Collection;
    }

    public function getRolesAttribute(): SupportCollection
    {
        return $this->rol ? collect([$this->rol]) : collect();
    }
}
