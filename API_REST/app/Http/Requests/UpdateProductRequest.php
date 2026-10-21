<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de solicitud para la actualización de productos existentes,
 * precios de venta, costos de compra unitarios y umbrales de stock mínimo.
 */
class UpdateProductRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'cost_price' => ['nullable', 'integer', 'min:0'],
            'price' => ['sometimes', 'required', 'integer', 'min:0'],
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
            'name.required' => 'El nombre del producto no puede quedar vacío al actualizar.',
            'price.required' => 'El precio de venta unitario es obligatorio.',
            'price.min' => 'El precio de venta no puede ser inferior a $0 COP.',
            'cost_price.min' => 'El costo de compra unitario no puede ser negativo.',
            'min_stock.min' => 'El stock mínimo de alerta debe ser mayor o igual a cero.',
            'stock.min' => 'La existencia en bodega no puede ser un número negativo.',
        ];
    }
}
