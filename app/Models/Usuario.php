<?php

namespace App\Models;

use App\Models\Usuarios\Rol;
use App\Models\Ventas\NotaVenta;
use App\Models\Servicios\ConsultaMedica;
use App\Traits\HasPermisosBd;
use App\Traits\SerializeDates;
use App\Traits\UsaTimestampsEspanol;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasPermisosBd;
    use HasProfilePhoto;
    use Notifiable;
    use SerializeDates;
    use TwoFactorAuthenticatable;
    use UsaTimestampsEspanol;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'email',
        'password',
        'esta_activo',
        'rol_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $appends = [
        'profile_photo_url',
        'full_name',
        'first_name',
        'last_name',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'esta_activo' => 'boolean',
            'password' => 'hashed',
        ];
    }

    protected static function newFactory()
    {
        return UsuarioFactory::new();
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function getRoleAttribute()
    {
        return $this->rol;
    }

    public function getFirstNameAttribute(): string
    {
        $partes = preg_split('/\s+/', trim($this->nombre ?? ''), 2);

        return $partes[0] ?? '';
    }

    public function getLastNameAttribute(): string
    {
        $partes = preg_split('/\s+/', trim($this->nombre ?? ''), 2);

        return $partes[1] ?? '';
    }

    public function setFirstNameAttribute(string $value): void
    {
        $this->attributes['nombre'] = trim($value.' '.($this->last_name ?? ''));
    }

    public function setLastNameAttribute(string $value): void
    {
        $this->attributes['nombre'] = trim(($this->first_name ?? '').' '.$value);
    }

    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->nombre ?? '',
        );
    }

    public function getFullNameAttribute(): string
    {
        return $this->nombre ?? '';
    }

    public function consultasMedicas(): HasMany
    {
        return $this->hasMany(ConsultaMedica::class, 'usuario_id');
    }

    public function notasVenta(): HasMany
    {
        return $this->hasMany(NotaVenta::class, 'usuario_id');
    }

    /** @deprecated Usar notasVenta() */
    public function saleNotes(): HasMany
    {
        return $this->notasVenta();
    }

    protected function defaultProfilePhotoUrl(): string
    {
        $name = trim(collect(explode(' ', $this->nombre ?? 'U'))->map(
            fn ($segment) => mb_substr($segment, 0, 1)
        )->join(' '));

        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&color=7F9CF5&background=EBF4FF';
    }
}
