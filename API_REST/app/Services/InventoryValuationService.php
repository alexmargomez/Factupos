<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Collection;

/**
 * Servicio de dominio para la valorización de inventario en bodega,
 * cálculo de márgenes unitarios y ponderados, alertas de punto de reorden
 * y distribución de stock por proveedor.
 */
class InventoryValuationService
{
    /**
     * Genera el informe integral de valorización de inventario y estado de bodega.
     *
     * @return array<string, mixed>
     */
    public function getWarehouseValuationReport(): array
    {
        $products = Product::with(['inventory', 'supplier', 'category'])
            ->orderBy('name')
            ->get();

        $totalSkus = $products->count();
        $totalUnitsInStock = 0;
        $totalCapitalAtCost = 0;
        $totalPotentialRevenue = 0;
        $outOfStockCount = 0;
        $lowStockCount = 0;

        $itemsValuation = [];
        $reorderAlerts = [];

        foreach ($products as $product) {
            $stock = (int) ($product->inventory->stock ?? $product->inventory->quantity ?? 0);
            $minStock = (int) ($product->min_stock ?? 5);
            $costPrice = (int) ($product->cost_price ?? 0);
            $salePrice = (int) ($product->price ?? 0);

            $unitProfit = $salePrice - $costPrice;
            $marginPct = $salePrice > 0
                ? round(($unitProfit / $salePrice) * 100, 2)
                : 0.0;
            $markupPct = $costPrice > 0
                ? round(($unitProfit / $costPrice) * 100, 2)
                : 100.0;

            $lineCostValue = max(0, $stock) * $costPrice;
            $lineSaleValue = max(0, $stock) * $salePrice;
            $lineProjectedProfit = $lineSaleValue - $lineCostValue;

            $totalUnitsInStock += max(0, $stock);
            $totalCapitalAtCost += $lineCostValue;
            $totalPotentialRevenue += $lineSaleValue;

            $stockStatus = 'OPTIMO';
            if ($stock <= 0) {
                $stockStatus = 'AGOTADO';
                $outOfStockCount++;
            } elseif ($stock <= $minStock) {
                $stockStatus = 'BAJO_MINIMO';
                $lowStockCount++;
            }

            $itemRow = [
                'id' => $product->id,
                'sku' => $product->sku ?: sprintf('PROD-%04d', $product->id),
                'name' => $product->name,
                'category_name' => $product->category->name ?? 'General',
                'supplier_id' => $product->supplier_id,
                'supplier_name' => $product->supplier->name ?? 'Sin proveedor asignado',
                'supplier_phone' => $product->supplier->phone ?? null,
                'stock' => $stock,
                'min_stock' => $minStock,
                'stock_status' => $stockStatus,
                'cost_price' => $costPrice,
                'sale_price' => $salePrice,
                'unit_profit' => $unitProfit,
                'margin_pct' => $marginPct,
                'markup_pct' => $markupPct,
                'total_cost_value' => $lineCostValue,
                'total_sale_value' => $lineSaleValue,
                'projected_profit' => $lineProjectedProfit,
            ];

            $itemsValuation[] = $itemRow;

            if ($stock <= $minStock) {
                $suggestedOrderQty = max($minStock * 2 - max(0, $stock), $minStock);
                $reorderAlerts[] = array_merge($itemRow, [
                    'deficit_units' => max(0, $minStock - $stock),
                    'suggested_order_qty' => $suggestedOrderQty,
                    'estimated_restock_cost' => $suggestedOrderQty * $costPrice,
                ]);
            }
        }

        $totalProjectedProfit = $totalPotentialRevenue - $totalCapitalAtCost;
        $weightedMarginPct = $totalPotentialRevenue > 0
            ? round(($totalProjectedProfit / $totalPotentialRevenue) * 100, 2)
            : 0.0;

        return [
            'summary' => [
                'total_skus' => $totalSkus,
                'total_units_in_stock' => $totalUnitsInStock,
                'total_capital_at_cost' => $totalCapitalAtCost,
                'total_potential_revenue' => $totalPotentialRevenue,
                'total_projected_profit' => $totalProjectedProfit,
                'weighted_margin_pct' => $weightedMarginPct,
                'out_of_stock_count' => $outOfStockCount,
                'low_stock_count' => $lowStockCount,
                'healthy_stock_count' => max(0, $totalSkus - $outOfStockCount - $lowStockCount),
            ],
            'supplier_distribution' => $this->buildSupplierInventoryDistribution($itemsValuation),
            'reorder_alerts' => $reorderAlerts,
            'items' => $itemsValuation,
        ];
    }

    /**
     * Agrupa la valorización de inventario por proveedor para negociaciones de reabastecimiento.
     *
     * @param array<int, array<string, mixed>> $itemsValuation
     * @return array<int, array<string, mixed>>
     */
    public function buildSupplierInventoryDistribution(array $itemsValuation): array
    {
        $grouped = [];

        foreach ($itemsValuation as $item) {
            $key = $item['supplier_id'] ?: 0;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'supplier_id' => $item['supplier_id'],
                    'supplier_name' => $item['supplier_name'],
                    'supplier_phone' => $item['supplier_phone'],
                    'skus_count' => 0,
                    'total_units' => 0,
                    'total_cost_value' => 0,
                    'total_sale_value' => 0,
                    'low_stock_skus' => 0,
                ];
            }

            $grouped[$key]['skus_count']++;
            $grouped[$key]['total_units'] += max(0, (int) $item['stock']);
            $grouped[$key]['total_cost_value'] += (int) $item['total_cost_value'];
            $grouped[$key]['total_sale_value'] += (int) $item['total_sale_value'];

            if ($item['stock_status'] !== 'OPTIMO') {
                $grouped[$key]['low_stock_skus']++;
            }
        }

        $result = array_values($grouped);
        usort($result, fn (array $a, array $b) => $b['total_cost_value'] <=> $a['total_cost_value']);

        return $result;
    }

    /**
     * Obtiene el resumen de movimientos recientes del kardex (entradas vs salidas).
     *
     * @param int $limit
     * @return array<string, mixed>
     */
    public function getRecentKardexActivity(int $limit = 50): array
    {
        $movements = InventoryMovement::with('inventory.product')
            ->orderByDesc('movement_date')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $incomingUnits = (int) $movements->where('movement_type', 'incoming')->sum('quantity');
        $outgoingUnits = (int) $movements->where('movement_type', 'outgoing')->sum('quantity');

        return [
            'analyzed_movements_count' => $movements->count(),
            'incoming_units_total' => $incomingUnits,
            'outgoing_units_total' => $outgoingUnits,
            'net_units_delta' => $incomingUnits - $outgoingUnits,
            'movements' => $movements->map(function (InventoryMovement $movement) {
                $product = $movement->inventory->product ?? null;
                return [
                    'id' => $movement->id,
                    'product_id' => $product->id ?? null,
                    'product_sku' => $product->sku ?? null,
                    'product_name' => $product->name ?? sprintf('Inventario #%d', $movement->inventory_id),
                    'movement_type' => $movement->movement_type,
                    'quantity' => (int) $movement->quantity,
                    'reason' => $movement->reason,
                    'movement_date' => $movement->movement_date,
                ];
            })->values()->all(),
        ];
    }
}
