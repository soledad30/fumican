<?php

namespace App\Models\Ventas;

use App\Traits\SerializeDates;
use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory, SerializeDates, UsaTimestampsEspanol;

    protected $table = 'categorias';

    protected $fillable = ['nombre'];

    protected $appends = ['name'];

    public function getNameAttribute(): ?string { return $this->attributes['nombre'] ?? null; }
    public function setNameAttribute($v): void { $this->attributes['nombre'] = $v; }

    public function medicaments() { return $this->hasMany(Producto::class, 'categoria_id'); }

    /** @deprecated Usar medicaments() */
    public function productos() { return $this->medicaments(); }
}
