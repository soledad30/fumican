<?php

namespace App\Models\Ventas;

use App\Traits\SerializeDates;
use App\Traits\UsaTimestampsEspanol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory, SerializeDates, UsaTimestampsEspanol;

    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'unidad_medida',
        'presentacion',
        'dosificacion',
        'fabricante',
        'fecha_vencimiento',
        'sustancia_controlada',
        'categoria_id',
        'stock_minimo',
        'precio_venta_referencia',
    ];

    protected $appends = [
        'name', 'dosage', 'manufacturer', 'expiration_date', 'controlled_substance', 'category_id',
        'unit', 'presentation', 'min_stock', 'reference_sale_price',
    ];

    protected $casts = [
        'sustancia_controlada' => 'boolean',
        'fecha_vencimiento' => 'date',
        'stock_minimo' => 'integer',
        'precio_venta_referencia' => 'float',
    ];

    public function getNameAttribute(): ?string { return $this->attributes['nombre'] ?? null; }
    public function setNameAttribute($v): void { $this->attributes['nombre'] = $v; }
    public function getDosageAttribute(): ?string { return $this->attributes['dosificacion'] ?? null; }
    public function setDosageAttribute($v): void { $this->attributes['dosificacion'] = $v; }
    public function getManufacturerAttribute(): ?string { return $this->attributes['fabricante'] ?? null; }
    public function setManufacturerAttribute($v): void { $this->attributes['fabricante'] = $v; }
    public function getExpirationDateAttribute() { return $this->attributes['fecha_vencimiento'] ?? null; }
    public function setExpirationDateAttribute($v): void { $this->attributes['fecha_vencimiento'] = $v; }
    public function getControlledSubstanceAttribute(): string
    {
        return ! empty($this->attributes['sustancia_controlada']) ? 'yes' : 'no';
    }

    public function setControlledSubstanceAttribute($value): void
    {
        $this->attributes['sustancia_controlada'] = $value === 'yes' || $value === true || $value === 1 || $value === '1';
    }
    public function getCategoryIdAttribute() { return $this->attributes['categoria_id'] ?? null; }
    public function setCategoryIdAttribute($v): void { $this->attributes['categoria_id'] = $v; }

    public function getUnitAttribute(): ?string { return $this->attributes['unidad_medida'] ?? 'unidad'; }
    public function setUnitAttribute($v): void { $this->attributes['unidad_medida'] = $v; }
    public function getPresentationAttribute(): ?string { return $this->attributes['presentacion'] ?? null; }
    public function setPresentationAttribute($v): void { $this->attributes['presentacion'] = $v; }
    public function getMinStockAttribute() { return $this->attributes['stock_minimo'] ?? 0; }
    public function setMinStockAttribute($v): void { $this->attributes['stock_minimo'] = $v; }
    public function getReferenceSalePriceAttribute() { return $this->attributes['precio_venta_referencia'] ?? null; }
    public function setReferenceSalePriceAttribute($v): void { $this->attributes['precio_venta_referencia'] = $v; }

    public function stockTotal(): int
    {
        return (int) $this->inventarios()->sum('stock');
    }

    public function categoria() { return $this->belongsTo(Categoria::class, 'categoria_id'); }
    public function inventarios() { return $this->hasMany(Inventario::class, 'producto_id'); }
    public function detallesNotaVenta() { return $this->hasMany(DetalleNotaVenta::class, 'producto_id'); }
    public function detallesNotaCompra() { return $this->hasMany(DetalleNotaCompra::class, 'producto_id'); }

    /** @deprecated */
    public function category() { return $this->categoria(); }
    /** @deprecated */
    public function inventories() { return $this->inventarios(); }
}
