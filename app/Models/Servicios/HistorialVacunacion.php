<?php

namespace App\Models\Servicios;

use App\Models\Usuario;
use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialVacunacion extends Model
{
    use UsaTimestampsEspanol;

    protected $table = 'historial_vacunacion';

    public $timestamps = true;

    public function getUpdatedAtColumn(): ?string
    {
        return null;
    }

    protected $fillable = [
        'mascota_id',
        'vacuna_id',
        'fecha_aplicacion',
        'fecha_proxima',
        'aplicado_por',
        'notas',
    ];

    protected $casts = [
        'fecha_aplicacion' => 'date',
        'fecha_proxima' => 'date',
    ];

    public function mascota(): BelongsTo
    {
        return $this->belongsTo(Mascota::class, 'mascota_id');
    }

    public function vacuna(): BelongsTo
    {
        return $this->belongsTo(Vacuna::class, 'vacuna_id');
    }

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'aplicado_por');
    }
}
