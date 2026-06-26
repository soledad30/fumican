<?php

namespace App\Models\Usuarios;

use App\Traits\SerializeDates;
use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Model;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    use SerializeDates, UsaTimestampsEspanol;

    protected $table = 'roles';

    protected $fillable = ['nombre', 'descripcion'];

    protected $appends = ['name', 'permissions'];

    public function getNameAttribute(): ?string
    {
        return $this->attributes['nombre'] ?? null;
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['nombre'] = $value;
    }

    public function getPermissionsAttribute()
    {
        if ($this->relationLoaded('permisos')) {
            return $this->getRelation('permisos');
        }

        return $this->permisos;
    }

    public function getCreatedAtAttribute()
    {
        return $this->attributes['creado_en'] ?? null;
    }

    public function getUpdatedAtAttribute()
    {
        return $this->attributes['actualizado_en'] ?? null;
    }

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'roles_permisos', 'rol_id', 'permiso_id')
            ->withPivot('creado_en');
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'rol_id');
    }

    public function syncPermisos(array $permisoIds): void
    {
        $this->permisos()->sync(
            collect($permisoIds)->mapWithKeys(fn ($id) => [$id => ['creado_en' => now()]])->all()
        );
    }

    /** @deprecated Usar syncPermisos() */
    public function syncPermissions(array $permissionIds): void
    {
        $this->syncPermisos($permissionIds);
    }

    /** @deprecated Usar permisos() */
    public function permissions(): BelongsToMany
    {
        return $this->permisos();
    }
}
