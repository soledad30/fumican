<?php

namespace App\Models\Servicios;

use App\Models\Ventas\Producto;
use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tratamiento extends Model
{
    use HasFactory, UsaTimestampsEspanol;

    protected $table = 'tratamientos';

    public const CREATED_AT = 'creado_en';

    public const UPDATED_AT = 'actualizado_en';

    public $timestamps = true;

    public function getUpdatedAtColumn(): ?string
    {
        return null;
    }

    protected $fillable = [
        'consulta_medica_id',
        'producto_id',
        'cantidad',
        'instrucciones_dosis',
        'notas',
    ];

    protected $appends = ['consulta_id'];

    public function getConsultaIdAttribute()
    {
        return $this->attributes['consulta_medica_id'] ?? $this->attributes['consulta_id'] ?? null;
    }

    public function setConsultaIdAttribute($value): void
    {
        $this->attributes['consulta_medica_id'] = $value;
    }

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(ConsultaMedica::class, 'consulta_medica_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /** @deprecated Usar consulta() */
    public function medicalConsultation(): BelongsTo
    {
        return $this->consulta();
    }
}
