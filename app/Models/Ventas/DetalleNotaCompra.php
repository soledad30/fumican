<?php

namespace App\Models\Ventas;

use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleNotaCompra extends Model
{
    use HasFactory, UsaTimestampsEspanol;

    protected $table = 'detalles_nota_compra';

    public $timestamps = true;

    public function getUpdatedAtColumn(): ?string
    {
        return null;
    }

    protected $fillable = ['cantidad', 'precio_compra', 'subtotal', 'nota_compra_id', 'producto_id'];

    protected $appends = ['quantity', 'purchase_price', 'purchase_note_id', 'medicament_id'];

    public function getQuantityAttribute() { return $this->attributes['cantidad'] ?? null; }
    public function setQuantityAttribute($v): void { $this->attributes['cantidad'] = $v; }
    public function getPurchasePriceAttribute() { return $this->attributes['precio_compra'] ?? null; }
    public function setPurchasePriceAttribute($v): void { $this->attributes['precio_compra'] = $v; }
    public function getPurchaseNoteIdAttribute() { return $this->attributes['nota_compra_id'] ?? null; }
    public function setPurchaseNoteIdAttribute($v): void { $this->attributes['nota_compra_id'] = $v; }
    public function getMedicamentIdAttribute() { return $this->attributes['producto_id'] ?? null; }
    public function setMedicamentIdAttribute($v): void { $this->attributes['producto_id'] = $v; }

    public function purchaseNote() { return $this->belongsTo(NotaCompra::class, 'nota_compra_id'); }
    public function medicament() { return $this->belongsTo(Producto::class, 'producto_id'); }

    public function producto(): BelongsTo
    {
        return $this->medicament();
    }
}
