<?php

namespace App\Models\Servicios;

use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Raza extends Model
{
    use HasFactory, UsaTimestampsEspanol;

    protected $table = 'razas';

    public $timestamps = true;

    protected $fillable = ['nombre', 'especie_id'];

    protected $appends = ['name', 'specie_id'];

    public function getNameAttribute(): ?string
    {
        return $this->attributes['nombre'] ?? null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['nombre'] = $value;
    }

    public function getSpecieIdAttribute()
    {
        return $this->attributes['especie_id'] ?? null;
    }

    public function setSpecieIdAttribute($value): void
    {
        $this->attributes['especie_id'] = $value;
    }

    public function especie(): BelongsTo
    {
        return $this->belongsTo(Especie::class, 'especie_id');
    }

    public function mascotas(): HasMany
    {
        return $this->hasMany(Mascota::class, 'raza_id');
    }

    /** @deprecated Usar especie() */
    public function specie(): BelongsTo
    {
        return $this->especie();
    }

    /** @deprecated Usar mascotas() */
    public function pets(): HasMany
    {
        return $this->mascotas();
    }
}
