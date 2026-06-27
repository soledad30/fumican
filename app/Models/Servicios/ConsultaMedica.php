<?php

namespace App\Models\Servicios;

use App\Models\Usuario;
use App\Support\ConsultaSaldo;
use App\Traits\SerializeDates;
use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsultaMedica extends Model
{
    use HasFactory, SerializeDates, UsaTimestampsEspanol;

    protected $table = 'consultas_medicas';

    protected $casts = [
        'reprogramada_tarde' => 'boolean',
    ];

    protected $fillable = [
        'fecha',
        'hora',
        'motivo',
        'diagnostico',
        'costo_consulta',
        'estado',
        'mascota_id',
        'usuario_id',
        'servicio_id',
        'reprogramada_tarde',
    ];

    protected $appends = [
        'reason', 'pet_id', 'veterinarian_id', 'consultation_fee',
        'confirmatory_diagnosis', 'presumptive_diagnosis', 'service_id',
        'monto_pagado', 'saldo_pendiente',
    ];

    public function getMontoPagadoAttribute(): float
    {
        return ConsultaSaldo::montoPagado($this);
    }

    public function getSaldoPendienteAttribute(): float
    {
        return ConsultaSaldo::saldoPendiente($this);
    }

    public function getReasonAttribute(): ?string
    {
        return $this->attributes['motivo'] ?? null;
    }

    public function setReasonAttribute($value): void
    {
        $this->attributes['motivo'] = $value;
    }

    public function getPetIdAttribute()
    {
        return $this->attributes['mascota_id'] ?? null;
    }

    public function setPetIdAttribute($value): void
    {
        $this->attributes['mascota_id'] = $value;
    }

    public function getVeterinarianIdAttribute()
    {
        return $this->attributes['usuario_id'] ?? null;
    }

    public function setVeterinarianIdAttribute($value): void
    {
        $this->attributes['usuario_id'] = $value;
    }

    public function getConsultationFeeAttribute()
    {
        return $this->attributes['costo_consulta'] ?? null;
    }

    public function setConsultationFeeAttribute($value): void
    {
        $this->attributes['costo_consulta'] = $value;
    }

    public function getConfirmatoryDiagnosisAttribute(): ?string
    {
        return $this->attributes['diagnostico'] ?? null;
    }

    public function setConfirmatoryDiagnosisAttribute($value): void
    {
        $this->attributes['diagnostico'] = $value;
    }

    public function getPresumptiveDiagnosisAttribute(): ?string
    {
        return $this->attributes['diagnostico'] ?? null;
    }

    public function getServiceIdAttribute()
    {
        return $this->attributes['servicio_id'] ?? null;
    }

    public function setServiceIdAttribute($value): void
    {
        $this->attributes['servicio_id'] = $value;
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'consulta_id');
    }

    public function mascota(): BelongsTo
    {
        return $this->belongsTo(Mascota::class, 'mascota_id');
    }

    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function tratamientos(): HasMany
    {
        return $this->hasMany(Tratamiento::class, 'consulta_medica_id');
    }

    /** @deprecated Usar mascota() */
    public function pet(): BelongsTo
    {
        return $this->mascota();
    }

    /** @deprecated Usar veterinario() */
    public function user(): BelongsTo
    {
        return $this->veterinario();
    }

    /** @deprecated Usar tratamientos() */
    public function treatments(): HasMany
    {
        return $this->tratamientos();
    }
}
