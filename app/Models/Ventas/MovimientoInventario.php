<?php

namespace App\Models\Ventas;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    public $timestamps = false;

    protected $fillable = [
        'producto_id',
        'almacen_id',
        'inventario_id',
        'detalle_nota_venta_id',
        'detalle_nota_compra_id',
        'tipo',
        'cantidad',
        'stock_anterior',
        'stock_posterior',
        'usuario_id',
        'notas',
        'creado_en',
    ];

    protected $casts = [
        'creado_en' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function inventario(): BelongsTo
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }

    public function detalleNotaVenta(): BelongsTo
    {
        return $this->belongsTo(DetalleNotaVenta::class, 'detalle_nota_venta_id');
    }

    public function detalleNotaCompra(): BelongsTo
    {
        return $this->belongsTo(DetalleNotaCompra::class, 'detalle_nota_compra_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
