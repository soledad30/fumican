<?php

namespace App\Http\Requests\Ventas;

use App\Enums\PermisoEnum;
use App\Http\Requests\Concerns\AutorizaPermiso;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotaCompraRequest extends FormRequest
{
    use AutorizaPermiso;

    public function authorize(): bool
    {
        return $this->usuarioPuede(PermisoEnum::EDITAR_NOTAS_DE_COMPRAS);
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => 'required|exists:almacenes,id',
            'supplier_id' => 'required|exists:proveedores,id',
            'total_amount' => 'required|numeric',
            'medicaments' => 'required|array',
            'medicaments.*.id' => 'required|exists:productos,id',
            'medicaments.*.quantity' => 'required|integer|min:1',
            'medicaments.*.purchase_price' => 'required|numeric|min:0',
            'medicaments.*.sale_price' => 'nullable|numeric|min:0',
            'medicaments.*.expiration_date' => 'nullable|date',
            'medicaments.*.subtotal' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required' => 'The Almacen field is required.',
            'warehouse_id.exists' => 'The selected Almacen is invalid.',
            'supplier_id.required' => 'The Proveedor field is required.',
            'supplier_id.exists' => 'The selected Proveedor is invalid.',
            'total_amount.required' => 'The total amount field is required.',
            'total_amount.numeric' => 'The total amount must be a number.',
            'medicaments.required' => 'The medicaments field is required.',
            'medicaments.array' => 'The medicaments must be an array.',
            'medicaments.*.id.required' => 'The Producto id field is required.',
            'medicaments.*.id.exists' => 'The selected Producto is invalid.',
            'medicaments.*.quantity.required' => 'The quantity field is required.',
            'medicaments.*.quantity.integer' => 'The quantity must be an integer.',
            'medicaments.*.quantity.min' => 'The quantity must be at least 1.',
            'medicaments.*.purchase_price.required' => 'The purchase price field is required.',
            'medicaments.*.purchase_price.numeric' => 'The purchase price must be a number.',
            'medicaments.*.purchase_price.min' => 'The purchase price must be at least 0.',
            'medicaments.*.subtotal.required' => 'The subtotal field is required.',
            'medicaments.*.subtotal.numeric' => 'The subtotal must be a number.',
            'medicaments.*.subtotal.min' => 'The subtotal must be at least 0.',
        ];
    }
}
