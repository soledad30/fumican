<?php

namespace App\Models\Auditoria;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuotaPago extends Model
{
    protected $connection = 'auditoria';

    protected $table = 'cuotas_pago';

    protected $fillable = [
        'plan_pago_id',
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
        'fecha_pago' => 'date',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlanPago::class, 'plan_pago_id');
    }
}
