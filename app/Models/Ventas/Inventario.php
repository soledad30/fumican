<?php

namespace App\Models\Ventas;

use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    use HasFactory, UsaTimestampsEspanol;

    protected $table = 'inventarios';

    protected $fillable = ['stock', 'precio', 'precio_compra', 'fecha_vencimiento', 'producto_id', 'almacen_id', 'detalle_nota_compra_id'];

    protected $appends = ['price', 'purchase_price', 'expiration_date', 'warehouse_id', 'medicament_id', 'purchase_note_detail_id'];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'precio' => 'float',
        'precio_compra' => 'float',
    ];

    public function getPriceAttribute() { return $this->attributes['precio'] ?? null; }
    public function setPriceAttribute($v): void { $this->attributes['precio'] = $v; }
    public function getPurchasePriceAttribute() { return $this->attributes['precio_compra'] ?? null; }
    public function setPurchasePriceAttribute($v): void { $this->attributes['precio_compra'] = $v; }
    public function getExpirationDateAttribute() { return $this->attributes['fecha_vencimiento'] ?? null; }
    public function setExpirationDateAttribute($v): void { $this->attributes['fecha_vencimiento'] = $v; }
    public function getWarehouseIdAttribute() { return $this->attributes['almacen_id'] ?? null; }
    public function setWarehouseIdAttribute($v): void { $this->attributes['almacen_id'] = $v; }
    public function getMedicamentIdAttribute() { return $this->attributes['producto_id'] ?? null; }
    public function setMedicamentIdAttribute($v): void { $this->attributes['producto_id'] = $v; }
    public function getPurchaseNoteDetailIdAttribute()
    {
        return $this->attributes['detalle_nota_compra_id'] ?? $this->attributes['purchase_note_detail_id'] ?? null;
    }
    public function setPurchaseNoteDetailIdAttribute($v): void
    {
        $this->attributes['detalle_nota_compra_id'] = $v;
    }

    public function warehouse() { return $this->belongsTo(Almacen::class, 'almacen_id'); }
    public function medicament() { return $this->belongsTo(Producto::class, 'producto_id'); }
    public function detalleNotaCompra() { return $this->belongsTo(DetalleNotaCompra::class, 'detalle_nota_compra_id'); }
}
