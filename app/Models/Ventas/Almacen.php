<?php

namespace App\Models\Ventas;

use App\Traits\SerializeDates;
use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Almacen extends Model
{
    use HasFactory, SerializeDates, UsaTimestampsEspanol;

    protected $table = 'almacenes';

    protected $fillable = ['nombre', 'ubicacion', 'descripcion'];

    protected $appends = ['name', 'location', 'description'];

    public function getNameAttribute(): ?string { return $this->attributes['nombre'] ?? null; }
    public function setNameAttribute($v): void { $this->attributes['nombre'] = $v; }
    public function getLocationAttribute(): ?string { return $this->attributes['ubicacion'] ?? null; }
    public function setLocationAttribute($v): void { $this->attributes['ubicacion'] = $v; }
    public function getDescriptionAttribute(): ?string { return $this->attributes['descripcion'] ?? null; }
    public function setDescriptionAttribute($v): void { $this->attributes['descripcion'] = $v; }

    public function inventories() { return $this->hasMany(Inventario::class, 'almacen_id'); }
}
