<?php

namespace App\Models\Auditoria;

use Illuminate\Database\Eloquent\Model;

class Visita extends Model
{
    protected $connection = 'auditoria';

    protected $table = 'visitas';

    public $timestamps = false;

    protected $fillable = [
        'ruta',
        'contador',
        'ultima_visita',
    ];

    protected $casts = [
        'ultima_visita' => 'datetime',
    ];
}
