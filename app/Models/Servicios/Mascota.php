<?php

namespace App\Models\Servicios;

use App\Traits\SerializeDates;
use App\Traits\UsaTimestampsEspanol;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;

class Mascota extends Model
{
    use HasFactory, SerializeDates, UsaTimestampsEspanol;

    protected $table = 'mascotas';

    protected $fillable = [
        'nombre',
        'peso',
        'color',
        'genero',
        'fecha_nacimiento',
        'url_foto',
        'cliente_id',
        'raza_id',
    ];

    protected $appends = ['name', 'weight', 'photo_url', 'customer_id', 'breed_id', 'gender', 'birth_date', 'age'];

    public function getNameAttribute(): ?string
    {
        return $this->attributes['nombre'] ?? null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['nombre'] = $value;
    }

    public function getWeightAttribute()
    {
        return $this->attributes['peso'] ?? null;
    }

    public function setWeightAttribute($value): void
    {
        $this->attributes['peso'] = $value;
    }

    public function getPhotoUrlAttribute(): ?string
    {
        $path = $this->attributes['url_foto'] ?? null;
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    public function setPhotoUrlAttribute($value): void
    {
        $this->attributes['url_foto'] = $value;
    }

    public function getCustomerIdAttribute()
    {
        return $this->attributes['cliente_id'] ?? null;
    }

    public function setCustomerIdAttribute($value): void
    {
        $this->attributes['cliente_id'] = $value;
    }

    public function getBreedIdAttribute()
    {
        return $this->attributes['raza_id'] ?? null;
    }

    public function setBreedIdAttribute($value): void
    {
        $this->attributes['raza_id'] = $value;
    }

    public function getGenderAttribute(): string
    {
        return match ((string) ($this->attributes['genero'] ?? '')) {
            '1', 'M', 'm', 'macho' => 'Macho',
            '0', 'F', 'f', 'hembra' => 'Hembra',
            default => $this->attributes['genero'] ?? 'N/A',
        };
    }

    public function getBirthDateAttribute()
    {
        return $this->attributes['fecha_nacimiento'] ?? null;
    }

    public function getAgeAttribute(): ?int
    {
        if (empty($this->attributes['fecha_nacimiento'])) {
            return null;
        }

        return (int) Carbon::parse($this->attributes['fecha_nacimiento'])->age;
    }

    public function raza(): BelongsTo
    {
        return $this->belongsTo(Raza::class, 'raza_id');
    }

    public function propietario(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /** Alias en español de propietario() */
    public function cliente(): BelongsTo
    {
        return $this->propietario();
    }

    public function consultas(): HasMany
    {
        return $this->hasMany(ConsultaMedica::class, 'mascota_id');
    }

    public function historialVacunacion(): HasMany
    {
        return $this->hasMany(HistorialVacunacion::class, 'mascota_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'mascota_id');
    }

    public function tratamientos(): HasManyThrough
    {
        return $this->hasManyThrough(
            Tratamiento::class,
            ConsultaMedica::class,
            'mascota_id',
            'consulta_medica_id'
        );
    }

    /** @deprecated Usar raza() */
    public function breed(): BelongsTo
    {
        return $this->raza();
    }

    /** @deprecated Usar propietario() */
    public function owner(): BelongsTo
    {
        return $this->propietario();
    }

    /** @deprecated Usar consultas() */
    public function medicalConsultations(): HasMany
    {
        return $this->consultas();
    }
}
