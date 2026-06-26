<?php

namespace App\Models\Usuarios;

use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permiso extends Model
{
    use UsaTimestampsEspanol;

    protected $table = 'permisos';

    protected $fillable = ['nombre', 'descripcion'];

    protected $appends = ['name'];

    public function getNameAttribute(): ?string
    {
        return $this->attributes['nombre'] ?? null;
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['nombre'] = $value;
    }

    public function getCreatedAtAttribute()
    {
        return $this->attributes['creado_en'] ?? null;
    }

    public function getUpdatedAtAttribute()
    {
        return $this->attributes['actualizado_en'] ?? null;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'roles_permisos', 'permiso_id', 'rol_id')
            ->withPivot('creado_en');
    }
}
