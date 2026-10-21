<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso JSON para la representación enriquecida de productos,
 * márgenes de comercialización y estado de inventario en bodega.
 */
class ProductResource extends JsonResource
{
    /**
     * Transforma el recurso en un arreglo serializable a JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $costPrice = (int) ($this->cost_price ?? 0);
        $salePrice = (int) ($this->price ?? 0);
        $stock = (int) ($this->stock ?? $this->inventory->quantity ?? 0);
        $minStock = (int) ($this->min_stock ?? 5);
        $unitProfit = $salePrice - $costPrice;
        $marginPct = $salePrice > 0 ? round(($unitProfit / $salePrice) * 100, 2) : 0.0;

        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'category_id' => $this->category_id,
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->whenLoaded('supplier', function () {
                return [
                    'id' => $this->supplier->id,
                    'name' => $this->supplier->name,
                    'nit' => $this->supplier->nit,
                    'phone' => $this->supplier->phone,
                ];
            }),
            'cost_price' => $costPrice,
            'price' => $salePrice,
            'unit_profit' => $unitProfit,
            'margin_percentage' => $marginPct,
            'stock' => $stock,
            'min_stock' => $minStock,
            'is_low_stock' => $stock <= $minStock,
            'is_out_of_stock' => $stock <= 0,
            'total_cost_valuation' => max(0, $stock) * $costPrice,
            'total_sale_valuation' => max(0, $stock) * $salePrice,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
