<?php

namespace App\Models\Servicios;

use App\Models\Usuario;
use App\Models\Ventas\NotaVenta;
use App\Traits\SerializeDates;
use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasFactory, SerializeDates, UsaTimestampsEspanol;

    protected $table = 'clientes';

    protected $fillable = [
        'usuario_id',
        'nombre',
        'apellido',
        'ci',
        'telefono',
        'email',
        'genero',
        'fecha_nacimiento',
        'direccion',
    ];

    protected $appends = ['full_name', 'first_name', 'last_name', 'phone_number', 'birthdate', 'address', 'gender', 'email'];

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

    public function getBirthdateAttribute()
    {
        return $this->attributes['fecha_nacimiento'] ?? null;
    }

    public function setBirthdateAttribute($value): void
    {
        $this->attributes['fecha_nacimiento'] = $value;
    }

    public function getAddressAttribute(): ?string
    {
        return $this->attributes['direccion'] ?? null;
    }

    public function setAddressAttribute($value): void
    {
        $this->attributes['direccion'] = $value;
    }

    public function getGenderAttribute(): int
    {
        return match ($this->attributes['genero'] ?? '') {
            'masculino', 'M', 'm', '0' => 0,
            'femenino', 'F', 'f', '1' => 1,
            'otro', '2' => 2,
            default => is_numeric($this->attributes['genero'] ?? null)
                ? (int) $this->attributes['genero']
                : 2,
        };
    }

    public function getEmailAttribute(): ?string
    {
        $almacenado = $this->attributes['email'] ?? null;
        if ($almacenado) {
            return $almacenado;
        }

        if ($this->relationLoaded('usuario')) {
            return $this->usuario?->email;
        }

        if ($this->usuario_id) {
            return $this->usuario()->value('email');
        }

        return null;
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = $value ?: null;
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(($this->nombre ?? '').' '.($this->apellido ?? '')),
        );
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->nombre ?? '').' '.($this->apellido ?? ''));
    }

    public function mascotas(): HasMany
    {
        return $this->hasMany(Mascota::class, 'cliente_id');
    }

    public function notasVenta(): HasMany
    {
        return $this->hasMany(NotaVenta::class, 'cliente_id');
    }

    /** @deprecated Usar mascotas() */
    public function pets(): HasMany
    {
        return $this->mascotas();
    }

    /** @deprecated Usar notasVenta() */
    public function saleNotes(): HasMany
    {
        return $this->notasVenta();
    }
}
