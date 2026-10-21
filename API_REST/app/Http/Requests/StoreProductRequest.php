<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de solicitud para la creación y actualización de productos
 * e inventario valorizado en el catálogo de FactuPOS.
 */
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'sku' => ['nullable', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:255'],
            'cost_price' => ['nullable', 'integer', 'min:0'],
            'price' => ['required', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'category_id' => ['nullable', 'integer'],
            'supplier_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre comercial del producto o repuesto es obligatorio.',
            'name.max' => 'El nombre del producto no puede superar los 255 caracteres.',
            'price.required' => 'El precio de venta al público es obligatorio.',
            'price.integer' => 'El precio de venta debe ser un valor entero en pesos ($ COP).',
            'price.min' => 'El precio de venta no puede ser negativo.',
            'cost_price.integer' => 'El costo de compra unitario debe ser un número entero.',
            'cost_price.min' => 'El costo de compra no puede ser menor a $0.',
            'min_stock.min' => 'El stock mínimo de alerta no puede ser negativo.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sku' => 'código de barras / SKU',
            'name' => 'nombre del producto',
            'cost_price' => 'costo de compra',
            'price' => 'precio de venta',
            'min_stock' => 'stock mínimo de alerta',
            'stock' => 'existencia inicial',
            'supplier_id' => 'proveedor',
        ];
    }
}
