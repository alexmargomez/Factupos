<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Service;
use App\Models\Vehicle;
use App\Models\Worker;
use Carbon\Carbon;

/**
 * Servicio de dominio para el análisis de fidelización de clientes (Customer Lifetime Value),
 * consolidación de hoja de vida clínica/técnica por vehículo, ranking de productos más vendidos
 * y productividad histórica por mecánico.
 */
class CustomerLoyaltyAndHistoryService
{
    /**
     * Construye el expediente técnico y comercial completo de un cliente,
     * incluyendo sus vehículos registrados, compras de repuestos, servicios de mano de obra
     * y estado de cartera pendiente.
     *
     * @param int $customerId
     * @return array<string, mixed>
     */
    public function buildCustomerDossier(int $customerId): array
    {
        $customer = Customer::with(['vehicles', 'sales.invoice', 'sales.worker'])
            ->findOrFail($customerId);

        $sales = $customer->sales ?? collect();
        $saleIds = $sales->pluck('id')->all();

        $purchasedItems = empty($saleIds)
            ? collect()
            : SaleDetail::with('product')->whereIn('sale_id', $saleIds)->get();

        $performedServices = Service::with(['vehicle', 'worker'])
            ->where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->get();

        $totalInvoiced = (int) $sales->sum('total');
        $totalPaid = (int) $sales->sum(fn (Sale $s) => (int) ($s->paid_amount ?? $s->total ?? 0));
        $totalPending = (int) $sales->sum(fn (Sale $s) => (int) ($s->pending_balance ?? 0));

        return [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'registered_at' => $customer->created_at?->toDateString(),
            ],
            'financial_summary' => [
                'visits_count' => $sales->count(),
                'lifetime_invoiced_value' => $totalInvoiced,
                'lifetime_paid_value' => $totalPaid,
                'current_pending_balance' => $totalPending,
                'average_ticket' => $sales->isNotEmpty()
                    ? (int) round($totalInvoiced / $sales->count())
                    : 0,
                'has_active_debt' => $totalPending > 0,
            ],
            'vehicles' => $customer->vehicles->map(function (Vehicle $v) {
                return [
                    'id' => $v->id,
                    'plate' => $v->plate,
                    'make' => $v->make,
                    'model' => $v->model,
                    'current_mileage' => $v->current_mileage,
                    'next_oil_change_km' => $v->next_oil_change_km,
                    'soat_due_date' => $v->soat_due_date,
                    'tecno_due_date' => $v->tecno_due_date,
                ];
            })->values()->all(),
            'purchased_parts_summary' => [
                'distinct_lines_count' => $purchasedItems->count(),
                'total_units_bought' => (int) $purchasedItems->sum('quantity'),
                'total_parts_spend' => (int) $purchasedItems->sum('subtotal'),
            ],
            'technical_services_history' => $performedServices->map(function (Service $srv) {
                return [
                    'id' => $srv->id,
                    'sale_id' => $srv->sale_id,
                    'vehicle_plate' => $srv->vehicle->plate ?? 'Mostrador',
                    'worker_name' => $srv->worker->name ?? 'General',
                    'service_description' => $srv->service,
                    'price_service' => (int) $srv->price_service,
                    'performed_at' => $srv->created_at?->toDateTimeString(),
                ];
            })->values()->all(),
        ];
    }

    /**
     * Genera el ranking de los repuestos/productos con mayor rotación y mayor aporte
     * a la utilidad bruta del negocio.
     *
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    public function getTopSellingProductsRanking(int $limit = 10): array
    {
        $details = SaleDetail::with('product.supplier')->get();

        $grouped = [];
        foreach ($details as $detail) {
            $productId = (int) $detail->product_id;
            $product = $detail->product;

            if (!isset($grouped[$productId])) {
                $grouped[$productId] = [
                    'product_id' => $productId,
                    'sku' => $product->sku ?? sprintf('PROD-%04d', $productId),
                    'name' => $product->name ?? "Producto #{$productId}",
                    'supplier_name' => $product->supplier->name ?? 'Sin proveedor',
                    'units_sold' => 0,
                    'total_revenue' => 0,
                    'total_estimated_cost' => 0,
                    'total_gross_profit' => 0,
                ];
            }

            $qty = max(0, (int) $detail->quantity);
            $revenue = (int) $detail->subtotal;
            $unitCost = (int) ($product->cost_price ?? 0);
            $cost = $qty * $unitCost;

            $grouped[$productId]['units_sold'] += $qty;
            $grouped[$productId]['total_revenue'] += $revenue;
            $grouped[$productId]['total_estimated_cost'] += $cost;
            $grouped[$productId]['total_gross_profit'] += ($revenue - $cost);
        }

        $ranking = array_values($grouped);
        usort($ranking, fn (array $a, array $b) => $b['units_sold'] <=> $a['units_sold']);

        return array_slice($ranking, 0, max(1, $limit));
    }

    /**
     * Calcula el ranking de productividad técnica por mecánico según los servicios
     * de mano de obra ejecutados y facturados.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getWorkerProductivityRanking(): array
    {
        $workers = Worker::with('settlements')->orderBy('name')->get();
        $services = Service::all()->groupBy('worker_id');

        $ranking = [];
        foreach ($workers as $worker) {
            $workerServices = $services->get($worker->id, collect());
            $totalHistoricalLabor = (int) $workerServices->sum('price_service');
            $commissionPct = max(0, min(100, (int) ($worker->commission_percentage ?? 50)));

            $ranking[] = [
                'worker_id' => $worker->id,
                'name' => $worker->name,
                'commission_percentage' => $commissionPct,
                'services_completed_count' => $workerServices->count(),
                'historical_labor_generated' => $totalHistoricalLabor,
                'current_unsettled_total_dia' => (int) ($worker->totalDia ?? 0),
                'historical_settlements_count' => $worker->settlements->count(),
                'historical_commissions_paid' => (int) $worker->settlements->sum('commission_amount'),
            ];
        }

        usort($ranking, fn (array $a, array $b) => $b['historical_labor_generated'] <=> $a['historical_labor_generated']);

        return $ranking;
    }
}
