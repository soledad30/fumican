<?php

namespace App\Models\Ventas;

use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleNotaVenta extends Model
{
    use HasFactory, UsaTimestampsEspanol;

    protected $table = 'detalles_nota_venta';

    public $timestamps = true;

    public function getUpdatedAtColumn(): ?string
    {
        return null;
    }

    protected $fillable = ['cantidad', 'precio_venta', 'subtotal', 'nota_venta_id', 'producto_id'];

    protected $appends = ['quantity', 'sale_price', 'sales_note_id', 'medicament_id'];

    public function getQuantityAttribute() { return $this->attributes['cantidad'] ?? null; }
    public function setQuantityAttribute($v): void { $this->attributes['cantidad'] = $v; }
    public function getSalePriceAttribute() { return $this->attributes['precio_venta'] ?? null; }
    public function setSalePriceAttribute($v): void { $this->attributes['precio_venta'] = $v; }
    public function getSalesNoteIdAttribute() { return $this->attributes['nota_venta_id'] ?? null; }
    public function setSalesNoteIdAttribute($v): void { $this->attributes['nota_venta_id'] = $v; }
    public function getMedicamentIdAttribute() { return $this->attributes['producto_id'] ?? null; }
    public function setMedicamentIdAttribute($v): void { $this->attributes['producto_id'] = $v; }

    public function salesNote() { return $this->belongsTo(NotaVenta::class, 'nota_venta_id'); }
    public function medicament() { return $this->belongsTo(Producto::class, 'producto_id'); }

    public function producto(): BelongsTo
    {
        return $this->medicament();
    }
}
