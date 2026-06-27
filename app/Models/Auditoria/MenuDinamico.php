<?php

namespace App\Models\Auditoria;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuDinamico extends Model
{
    protected $table = 'menus';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'icono',
        'enlace',
        'permiso_bd',
        'permiso_id',
        'parent_id',
        'orden',
    ];

    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function permiso(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Usuarios\Permiso::class, 'permiso_id');
    }

    public function hijos(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('orden');
    }
}
