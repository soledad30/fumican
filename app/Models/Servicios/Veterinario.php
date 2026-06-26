<?php

namespace App\Models\Servicios;

use App\Models\Usuario;
use App\Traits\SerializeDates;
use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Veterinario extends Model
{
    use HasFactory, SerializeDates, UsaTimestampsEspanol;

    protected $table = 'veterinarios';

    protected $fillable = [
        'usuario_id',
        'nombre',
        'apellido',
        'ci',
        'telefono',
        'email',
        'licencia',
        'es_especialista',
        'especialidad',
        'esta_activo',
    ];

    protected function casts(): array
    {
        return [
            'es_especialista' => 'boolean',
            'esta_activo' => 'boolean',
        ];
    }

    protected $appends = [
        'full_name',
        'first_name',
        'last_name',
        'phone_number',
        'is_specialist',
        'specialty',
        'license_number',
    ];

    public function getFirstNameAttribute(): ?string
    {
        return $this->attributes['nombre'] ?? null;
    }

    public function setFirstNameAttribute($value): void
    {
        $this->attributes['nombre'] = $value;
    }

    public function getLastNameAttribute(): ?string
    {
        return $this->attributes['apellido'] ?? null;
    }

    public function setLastNameAttribute($value): void
    {
        $this->attributes['apellido'] = $value;
    }

    public function getPhoneNumberAttribute(): ?string
    {
        return $this->attributes['telefono'] ?? null;
    }

    public function setPhoneNumberAttribute($value): void
    {
        $this->attributes['telefono'] = $value;
    }

    public function getIsSpecialistAttribute(): bool
    {
        return (bool) ($this->attributes['es_especialista'] ?? false);
    }

    public function setIsSpecialistAttribute($value): void
    {
        $this->attributes['es_especialista'] = (bool) $value;
    }

    public function getSpecialtyAttribute(): ?string
    {
        return $this->attributes['especialidad'] ?? null;
    }

    public function setSpecialtyAttribute($value): void
    {
        $this->attributes['especialidad'] = $value;
    }

    public function getLicenseNumberAttribute(): ?string
    {
        return $this->attributes['licencia'] ?? null;
    }

    public function setLicenseNumberAttribute($value): void
    {
        $this->attributes['licencia'] = $value;
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->nombre ?? '').' '.($this->apellido ?? ''));
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
