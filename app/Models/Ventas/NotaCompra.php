<?php

namespace App\Models\Ventas;

use App\Models\Usuario;
use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaCompra extends Model
{
    use HasFactory, UsaTimestampsEspanol;

    protected $table = 'notas_compra';

    public $timestamps = true;

    public function getUpdatedAtColumn(): ?string
    {
        return null;
    }

    protected $fillable = [
        'fecha_compra',
        'monto_total',
        'proveedor_id',
        'almacen_id',
        'usuario_id',
    ];

    protected $appends = ['warehouse_id', 'supplier_id', 'user_id', 'purchase_date', 'total_amount'];

    protected $casts = ['fecha_compra' => 'date', 'monto_total' => 'float'];

    public function getWarehouseIdAttribute() { return $this->attributes['almacen_id'] ?? null; }
    public function setWarehouseIdAttribute($v): void { $this->attributes['almacen_id'] = $v; }
    public function getSupplierIdAttribute() { return $this->attributes['proveedor_id'] ?? null; }
    public function setSupplierIdAttribute($v): void { $this->attributes['proveedor_id'] = $v; }
    public function getUserIdAttribute() { return $this->attributes['usuario_id'] ?? null; }
    public function setUserIdAttribute($v): void { $this->attributes['usuario_id'] = $v; }
    public function getPurchaseDateAttribute() { return $this->attributes['fecha_compra'] ?? null; }
    public function setPurchaseDateAttribute($v): void { $this->attributes['fecha_compra'] = $v; }
    public function getTotalAmountAttribute() { return $this->attributes['monto_total'] ?? null; }
    public function setTotalAmountAttribute($v): void { $this->attributes['monto_total'] = $v; }

    public function proveedor() { return $this->belongsTo(Proveedor::class, 'proveedor_id'); }
    public function usuario() { return $this->belongsTo(Usuario::class, 'usuario_id'); }
    public function almacen() { return $this->belongsTo(Almacen::class, 'almacen_id'); }
    public function detalles() { return $this->hasMany(DetalleNotaCompra::class, 'nota_compra_id'); }
    public function supplier() { return $this->proveedor(); }
    public function warehouse() { return $this->almacen(); }
    public function purchaseNoteDetails() { return $this->detalles(); }
}
