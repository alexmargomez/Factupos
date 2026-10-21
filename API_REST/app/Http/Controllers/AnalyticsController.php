<?php

namespace App\Http\Controllers;

use App\Services\FinancialReportService;
use App\Services\InventoryValuationService;
use App\Services\WorkerCommissionService;
use App\Services\WorkshopKanbanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de analítica ejecutiva y reportes consolidados para FactuPOS.
 * Orquesta los servicios de dominio financiero, valorización de bodega,
 * tablero Kanban de taller y comisiones de mecánicos.
 */
class AnalyticsController extends Controller
{
    public function __construct(
        protected FinancialReportService $financialReportService,
        protected InventoryValuationService $inventoryValuationService,
        protected WorkshopKanbanService $workshopKanbanService,
        protected WorkerCommissionService $workerCommissionService
    ) {
    }

    /**
     * Retorna el consolidado ejecutivo unificado de todos los módulos del sistema.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function summary(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        return response()->json([
            'financial' => $this->financialReportService->buildPeriodSummary($startDate, $endDate),
            'inventory_valuation' => $this->inventoryValuationService->getWarehouseValuationReport(),
            'workshop_kanban' => $this->workshopKanbanService->getKanbanBoardSummary(),
            'vehicle_alerts' => $this->workshopKanbanService->getVehicleMaintenanceAlerts(
                (int) $request->query('days_threshold', 15),
                (int) $request->query('km_threshold', 500)
            ),
            'worker_commissions' => $this->workerCommissionService->getPendingCommissionsOverview(),
        ], 200);
    }

    /**
     * Retorna el arqueo de caja, utilidad neta y antigüedad de cartera por cobrar.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function financial(Request $request): JsonResponse
    {
        return response()->json(
            $this->financialReportService->buildPeriodSummary(
                $request->query('start_date'),
                $request->query('end_date')
            ),
            200
        );
    }

    /**
     * Retorna el reporte de valorización de inventario, márgenes y alertas de reorden.
     *
     * @return JsonResponse
     */
    public function inventoryValuation(): JsonResponse
    {
        return response()->json(
            $this->inventoryValuationService->getWarehouseValuationReport(),
            200
        );
    }

    /**
     * Retorna las alertas preventivas de cambio de aceite, SOAT y Tecnomecánica.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function vehicleAlerts(Request $request): JsonResponse
    {
        return response()->json(
            $this->workshopKanbanService->getVehicleMaintenanceAlerts(
                (int) $request->query('days_threshold', 15),
                (int) $request->query('km_threshold', 500)
            ),
            200
        );
    }
}
