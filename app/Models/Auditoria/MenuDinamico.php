<?php

namespace App\Models\Auditoria;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuDinamico extends Model
{
    protected $connection = 'auditoria';

    protected $table = 'menus';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'icono',
        'enlace',
        'permiso_bd',
        'parent_id',
        'orden',
    ];

    public function hijos(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('orden');
    }
}
