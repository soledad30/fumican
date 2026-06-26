<?php

namespace App\Models\Servicios;

use App\Models\Usuario;
use App\Models\Ventas\NotaVenta;
use App\Traits\SerializeDates;
use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use HasFactory, SerializeDates, UsaTimestampsEspanol;

    protected $table = 'pagos';

    public $timestamps = true;

    public function getUpdatedAtColumn(): ?string
    {
        return null;
    }

    protected $fillable = [
        'fecha_pago',
        'monto',
        'metodo_pago',
        'tipo_pago',
        'concepto_pago',
        'id_transaccion_externa',
        'nota_venta_id',
        'consulta_id',
        'servicio_id',
        'cliente_id',
        'mascota_id',
        'usuario_id',
    ];

    protected $casts = [
        'monto' => 'float',
        'fecha_pago' => 'datetime',
    ];

    public function notaVenta(): BelongsTo
    {
        return $this->belongsTo(NotaVenta::class, 'nota_venta_id');
    }

    public function consulta(): BelongsTo
    {
        return $this->belongsTo(ConsultaMedica::class, 'consulta_id');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function mascota(): BelongsTo
    {
        return $this->belongsTo(Mascota::class, 'mascota_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
