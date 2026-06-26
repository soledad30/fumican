<?php

namespace App\Models\Servicios;

use App\Traits\SerializeDates;
use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servicio extends Model
{
    use HasFactory, SerializeDates, UsaTimestampsEspanol;

    protected $table = 'servicios';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'esta_activo',
    ];

    protected $casts = [
        'esta_activo' => 'boolean',
        'precio' => 'float',
    ];

    public function consultas(): HasMany
    {
        return $this->hasMany(ConsultaMedica::class, 'servicio_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'servicio_id');
    }
}
