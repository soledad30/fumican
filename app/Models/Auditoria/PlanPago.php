<?php

namespace App\Models\Auditoria;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanPago extends Model
{
    protected $connection = 'auditoria';

    protected $table = 'planes_pago';

    protected $fillable = [
        'pago_id',
        'usuario_id',
        'nota_venta_id',
        'monto_total',
        'num_cuotas',
        'estado',
    ];

    protected $casts = [
        'monto_total' => 'float',
    ];

    public function cuotas(): HasMany
    {
        return $this->hasMany(CuotaPago::class, 'plan_pago_id')->orderBy('numero');
    }
}
