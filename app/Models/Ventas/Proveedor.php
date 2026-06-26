<?php

namespace App\Models\Ventas;

use App\Traits\SerializeDates;
use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory, SerializeDates, UsaTimestampsEspanol;

    protected $table = 'proveedores';

    protected $fillable = ['nombre', 'pais', 'telefono', 'email', 'direccion'];

    protected $appends = ['name', 'country', 'phoneNumber', 'address'];

    public function getNameAttribute(): ?string { return $this->attributes['nombre'] ?? null; }
    public function setNameAttribute($v): void { $this->attributes['nombre'] = $v; }
    public function getCountryAttribute(): ?string { return $this->attributes['pais'] ?? null; }
    public function setCountryAttribute($v): void { $this->attributes['pais'] = $v; }
    public function getPhoneNumberAttribute(): ?string { return $this->attributes['telefono'] ?? null; }
    public function setPhoneNumberAttribute($v): void { $this->attributes['telefono'] = $v; }
    public function getAddressAttribute(): ?string { return $this->attributes['direccion'] ?? null; }
    public function setAddressAttribute($v): void { $this->attributes['direccion'] = $v; }

    public function getCreatedAtAttribute()
    {
        return $this->attributes['creado_en'] ?? null;
    }

    public function getUpdatedAtAttribute()
    {
        return $this->attributes['actualizado_en'] ?? null;
    }
}
