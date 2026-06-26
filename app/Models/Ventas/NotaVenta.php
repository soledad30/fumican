<?php

namespace App\Models\Ventas;

use App\Models\Servicios\Cliente;
use App\Models\Servicios\Pago;
use App\Models\Usuario;
use App\Support\NotaVentaSaldo;
use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaVenta extends Model
{
    use HasFactory, UsaTimestampsEspanol;

    protected $table = 'notas_venta';

    public $timestamps = true;

    public function getUpdatedAtColumn(): ?string
    {
        return null;
    }

    protected $fillable = [
        'fecha_venta',
        'monto_total',
        'estado',
        'cliente_id',
        'usuario_id',
        'almacen_id',
        'consulta_id',
    ];

    protected $appends = [
        'customer_id', 'user_id', 'sale_date', 'total_amount', 'warehouse_id',
        'monto_pagado', 'saldo_pendiente',
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'monto_total' => 'float',
    ];

    public function getCustomerIdAttribute() { return $this->attributes['cliente_id'] ?? null; }
    public function setCustomerIdAttribute($v): void { $this->attributes['cliente_id'] = $v; }
    public function getUserIdAttribute() { return $this->attributes['usuario_id'] ?? null; }
    public function setUserIdAttribute($v): void { $this->attributes['usuario_id'] = $v; }
    public function getSaleDateAttribute() { return $this->attributes['fecha_venta'] ?? null; }
    public function setSaleDateAttribute($v): void { $this->attributes['fecha_venta'] = $v; }
    public function getTotalAmountAttribute() { return $this->attributes['monto_total'] ?? null; }
    public function setTotalAmountAttribute($v): void { $this->attributes['monto_total'] = $v; }
    public function getWarehouseIdAttribute() { return $this->attributes['almacen_id'] ?? null; }
    public function setWarehouseIdAttribute($v): void { $this->attributes['almacen_id'] = $v; }

    public function getMontoPagadoAttribute(): float
    {
        return NotaVentaSaldo::montoPagado($this);
    }

    public function getSaldoPendienteAttribute(): float
    {
        return NotaVentaSaldo::saldoPendiente($this);
    }

    public function usuario() { return $this->belongsTo(Usuario::class, 'usuario_id'); }
    public function cliente() { return $this->belongsTo(Cliente::class, 'cliente_id'); }
    public function almacen() { return $this->belongsTo(Almacen::class, 'almacen_id'); }
    public function detalles() { return $this->hasMany(DetalleNotaVenta::class, 'nota_venta_id'); }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'nota_venta_id');
    }

    /** @deprecated */
    public function user() { return $this->usuario(); }
    /** @deprecated */
    public function customer() { return $this->cliente(); }
    /** @deprecated */
    public function warehouse() { return $this->almacen(); }
    /** @deprecated */
    public function salesNoteDetails() { return $this->detalles(); }
}
