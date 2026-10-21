<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso JSON para la representación de movimientos de kardex de inventario.
 */
class InventoryMovementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $product = $this->inventory->product ?? null;

        return [
            'id' => $this->id,
            'inventory_id' => $this->inventory_id,
            'product_id' => $product->id ?? null,
            'product_sku' => $product->sku ?? null,
            'product_name' => $product->name ?? sprintf('Inventario #%d', $this->inventory_id),
            'movement_type' => $this->movement_type,
            'movement_type_label' => $this->movement_type === 'incoming' ? 'Entrada' : 'Salida',
            'quantity' => (int) $this->quantity,
            'signed_quantity' => $this->movement_type === 'incoming'
                ? '+' . (int) $this->quantity
                : '-' . (int) $this->quantity,
            'reason' => $this->reason,
            'movement_date' => $this->movement_date,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
