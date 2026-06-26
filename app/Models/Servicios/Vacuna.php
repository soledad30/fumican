<?php

namespace App\Models\Servicios;

use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vacuna extends Model
{
    use HasFactory, UsaTimestampsEspanol;

    protected $table = 'vacunas';

    public $timestamps = true;

    public function getUpdatedAtColumn(): ?string
    {
        return null;
    }

    protected $fillable = [
        'nombre',
        'duracion_dias',
        'notas',
    ];

    public function historial(): HasMany
    {
        return $this->hasMany(HistorialVacunacion::class, 'vacuna_id');
    }
}
