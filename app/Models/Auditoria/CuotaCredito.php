<?php

namespace App\Models\Auditoria;

use App\Models\Servicios\Pago;
use App\Models\Usuario;
use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuotaCredito extends Model
{
    use UsaTimestampsEspanol;

    protected $table = 'cuotas_credito';

    protected $fillable = [
        'pago_id',
        'usuario_id',
        'numero',
        'monto',
        'fecha_vencimiento',
        'fecha_pago',
        'estado',
        'metodo_pago',
        'id_transaccion_externa',
    ];

    protected $casts = [
        'monto' => 'float',
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'datetime',
    ];

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
