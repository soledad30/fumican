<?php

namespace App\Models\Servicios;

use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Especie extends Model
{
    use HasFactory, UsaTimestampsEspanol;

    protected $table = 'especies';

    public $timestamps = true;

    protected $fillable = ['nombre'];

    protected $appends = ['name'];

    public function getNameAttribute(): ?string
    {
        return $this->attributes['nombre'] ?? null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['nombre'] = $value;
    }

    public function razas(): HasMany
    {
        return $this->hasMany(Raza::class, 'especie_id');
    }

    /** @deprecated Usar razas() */
    public function breeds(): HasMany
    {
        return $this->razas();
    }
}
