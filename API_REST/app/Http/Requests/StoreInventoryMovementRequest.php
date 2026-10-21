<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de solicitud para movimientos de inventario (entradas de compras y salidas de kardex).
 */
class StoreInventoryMovementRequest extends FormRequest
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
            'inventory_id' => ['nullable', 'integer', 'exists:Inventory,id'],
            'product_id' => ['nullable', 'integer', 'exists:Products,id'],
            'movement_type' => ['required', 'string', Rule::in(['incoming', 'outgoing'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_cost' => ['nullable', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
            'movement_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'movement_type.required' => 'Debes especificar si el movimiento es una entrada (incoming) o salida (outgoing).',
            'movement_type.in' => 'El tipo de movimiento de inventario debe ser incoming u outgoing.',
            'quantity.required' => 'La cantidad de unidades del movimiento es obligatoria.',
            'quantity.min' => 'La cantidad del movimiento debe ser de al menos 1 unidad.',
            'unit_cost.min' => 'El costo unitario de entrada no puede ser negativo.',
        ];
    }
}
