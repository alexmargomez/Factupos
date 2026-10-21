<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Service;
use App\Models\WorkerSettlement;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Servicio de dominio para la consolidación financiera, arqueo de caja,
 * cálculo de utilidad neta real y análisis de cuentas por cobrar (cartera).
 */
class FinancialReportService
{
    /**
     * Genera el consolidado financiero completo para un rango de fechas o período.
     *
     * @param string|null $startDate Fecha inicial en formato Y-m-d
     * @param string|null $endDate   Fecha final en formato Y-m-d
     * @return array<string, mixed>
     */
    public function buildPeriodSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : null;

        $salesQuery = Sale::with(['customer', 'vehicle', 'worker', 'invoice', 'saleDetails.product']);
        $expensesQuery = Expense::query();
        $settlementsQuery = WorkerSettlement::with('worker');

        if ($start && $end) {
            $salesQuery->whereBetween('created_at', [$start, $end]);
            $expensesQuery->whereBetween('expense_date', [$start, $end]);
            $settlementsQuery->whereBetween('settled_at', [$start, $end]);
        } elseif ($start) {
            $salesQuery->where('created_at', '>=', $start);
            $expensesQuery->where('expense_date', '>=', $start);
            $settlementsQuery->where('settled_at', '>=', $start);
        }

        $sales = $salesQuery->orderByDesc('created_at')->get();
        $expenses = $expensesQuery->orderByDesc('expense_date')->get();
        $settlements = $settlementsQuery->orderByDesc('settled_at')->get();

        $totalInvoiced = (int) $sales->sum('total');
        $totalCollected = (int) $sales->sum(function (Sale $sale) {
            return (int) ($sale->paid_amount ?? $sale->total ?? 0);
        });
        $totalPendingReceivables = (int) $sales->sum(function (Sale $sale) {
            return (int) ($sale->pending_balance ?? 0);
        });

        $partsMetrics = $this->calculatePartsRevenueAndCost($sales);
        $laborRevenue = $this->calculateLaborRevenue($sales);
        $totalExpenses = (int) $expenses->sum('amount');
        $totalWorkerPayouts = (int) $settlements->sum('commission_amount');

        $grossProfit = $partsMetrics['gross_profit'] + $laborRevenue;
        $netOperatingBalance = $totalCollected - $totalExpenses - $totalWorkerPayouts;
        $realNetProfit = $grossProfit - $totalExpenses - $totalWorkerPayouts;

        return [
            'period' => [
                'start_date' => $start ? $start->toDateString() : 'HISTÓRICO',
                'end_date' => $end ? $end->toDateString() : Carbon::now()->toDateString(),
                'generated_at' => Carbon::now()->toIso8601String(),
            ],
            'kpis' => [
                'sales_count' => $sales->count(),
                'total_invoiced' => $totalInvoiced,
                'total_collected' => $totalCollected,
                'total_pending_receivables' => $totalPendingReceivables,
                'collection_efficiency_pct' => $totalInvoiced > 0
                    ? round(($totalCollected / $totalInvoiced) * 100, 2)
                    : 100.0,
                'parts_revenue' => $partsMetrics['revenue'],
                'parts_cost' => $partsMetrics['cost'],
                'parts_gross_profit' => $partsMetrics['gross_profit'],
                'parts_margin_pct' => $partsMetrics['margin_pct'],
                'labor_revenue' => $laborRevenue,
                'total_expenses' => $totalExpenses,
                'total_worker_payouts' => $totalWorkerPayouts,
                'net_operating_cash' => $netOperatingBalance,
                'real_net_profit' => $realNetProfit,
            ],
            'payment_methods_breakdown' => $this->groupSalesByPaymentMethod($sales),
            'expenses_by_category' => $this->groupExpensesByCategory($expenses),
            'receivables_portfolio' => $this->buildReceivablesAging($sales),
        ];
    }

    /**
     * Calcula ingresos, costos de adquisición y utilidad bruta de repuestos vendidos.
     *
     * @param Collection<int, Sale> $sales
     * @return array{revenue: int, cost: int, gross_profit: int, margin_pct: float, units_sold: int}
     */
    public function calculatePartsRevenueAndCost(Collection $sales): array
    {
        if ($sales->isEmpty()) {
            return [
                'revenue' => 0,
                'cost' => 0,
                'gross_profit' => 0,
                'margin_pct' => 0.0,
                'units_sold' => 0,
            ];
        }

        $saleIds = $sales->pluck('id')->all();
        $details = SaleDetail::with('product')
            ->whereIn('sale_id', $saleIds)
            ->get();

        $revenue = 0;
        $cost = 0;
        $unitsSold = 0;

        foreach ($details as $detail) {
            $qty = max(0, (int) $detail->quantity);
            $subtotal = (int) ($detail->subtotal ?? ($qty * (int) ($detail->product->price ?? 0)));
            $unitCost = (int) ($detail->product->cost_price ?? 0);

            $revenue += $subtotal;
            $cost += ($qty * $unitCost);
            $unitsSold += $qty;
        }

        $grossProfit = $revenue - $cost;
        $marginPct = $revenue > 0 ? round(($grossProfit / $revenue) * 100, 2) : 0.0;

        return [
            'revenue' => $revenue,
            'cost' => $cost,
            'gross_profit' => $grossProfit,
            'margin_pct' => $marginPct,
            'units_sold' => $unitsSold,
        ];
    }

    /**
     * Calcula el ingreso bruto generado por mano de obra en un conjunto de ventas.
     *
     * @param Collection<int, Sale> $sales
     * @return int
     */
    public function calculateLaborRevenue(Collection $sales): int
    {
        if ($sales->isEmpty()) {
            return 0;
        }

        $saleIds = $sales->pluck('id')->all();
        return (int) Service::whereIn('sale_id', $saleIds)->sum('price_service');
    }

    /**
     * Agrupa las ventas según su método de pago (Efectivo, Transferencia/Nequi, Tarjeta, Pago Mixto, Crédito).
     *
     * @param Collection<int, Sale> $sales
     * @return array<int, array<string, mixed>>
     */
    public function groupSalesByPaymentMethod(Collection $sales): array
    {
        $grouped = $sales->groupBy(function (Sale $sale) {
            return $sale->payment_method ?: 'Efectivo';
        });

        $result = [];
        foreach ($grouped as $method => $items) {
            $result[] = [
                'payment_method' => $method,
                'transactions_count' => $items->count(),
                'total_invoiced' => (int) $items->sum('total'),
                'total_collected' => (int) $items->sum(fn (Sale $s) => (int) ($s->paid_amount ?? $s->total ?? 0)),
                'total_pending' => (int) $items->sum(fn (Sale $s) => (int) ($s->pending_balance ?? 0)),
            ];
        }

        usort($result, fn (array $a, array $b) => $b['total_invoiced'] <=> $a['total_invoiced']);

        return $result;
    }

    /**
     * Agrupa los gastos menores y salidas de caja por categoría contable.
     *
     * @param Collection<int, Expense> $expenses
     * @return array<int, array<string, mixed>>
     */
    public function groupExpensesByCategory(Collection $expenses): array
    {
        $grouped = $expenses->groupBy(function (Expense $expense) {
            return $expense->category ?: 'Operativo';
        });

        $result = [];
        foreach ($grouped as $category => $items) {
            $result[] = [
                'category' => $category,
                'items_count' => $items->count(),
                'total_amount' => (int) $items->sum('amount'),
            ];
        }

        usort($result, fn (array $a, array $b) => $b['total_amount'] <=> $a['total_amount']);

        return $result;
    }

    /**
     * Construye el estado de cartera y antigüedad de saldos pendientes por cobrar.
     *
     * @param Collection<int, Sale> $sales
     * @return array<string, mixed>
     */
    public function buildReceivablesAging(Collection $sales): array
    {
        $pendingSales = $sales->filter(function (Sale $sale) {
            return (int) ($sale->pending_balance ?? 0) > 0;
        });

        $buckets = [
            'current_0_7_days' => 0,
            'overdue_8_15_days' => 0,
            'overdue_16_30_days' => 0,
            'critical_over_30_days' => 0,
        ];

        $accounts = [];
        $now = Carbon::now();

        foreach ($pendingSales as $sale) {
            $createdAt = $sale->created_at ? Carbon::parse($sale->created_at) : $now;
            $daysElapsed = max(0, (int) $createdAt->diffInDays($now));
            $pendingAmount = (int) $sale->pending_balance;

            if ($daysElapsed <= 7) {
                $buckets['current_0_7_days'] += $pendingAmount;
                $agingLabel = 'Al día (0-7 días)';
            } elseif ($daysElapsed <= 15) {
                $buckets['overdue_8_15_days'] += $pendingAmount;
                $agingLabel = 'Mora temprana (8-15 días)';
            } elseif ($daysElapsed <= 30) {
                $buckets['overdue_16_30_days'] += $pendingAmount;
                $agingLabel = 'Mora media (16-30 días)';
            } else {
                $buckets['critical_over_30_days'] += $pendingAmount;
                $agingLabel = 'Cartera crítica (+30 días)';
            }

            $accounts[] = [
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice->invoice_number ?? sprintf('FACT-%06d', $sale->id),
                'customer_id' => $sale->customer_id,
                'customer_name' => $sale->customer->name ?? 'Cliente General',
                'customer_phone' => $sale->customer->phone ?? null,
                'vehicle_plate' => $sale->vehicle->plate ?? null,
                'total_sale' => (int) $sale->total,
                'paid_amount' => (int) ($sale->paid_amount ?? 0),
                'pending_balance' => $pendingAmount,
                'days_elapsed' => $daysElapsed,
                'aging_bucket' => $agingLabel,
                'payment_notes' => $sale->payment_notes,
            ];
        }

        return [
            'pending_invoices_count' => count($accounts),
            'total_receivables' => array_sum($buckets),
            'aging_buckets' => $buckets,
            'accounts' => $accounts,
        ];
    }
}
