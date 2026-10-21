<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Vehicle;
use Carbon\Carbon;

/**
 * Servicio de dominio para la gestión del tablero Kanban de taller,
 * seguimiento de órdenes de trabajo y detección proactiva de alertas
 * de mantenimiento (cambio de aceite, SOAT y revisión Tecnomecánica).
 */
class WorkshopKanbanService
{
    /**
     * Etapas oficiales del flujo Kanban del taller.
     *
     * @var array<int, string>
     */
    public const KANBAN_STAGES = [
        'Recibido',
        'En Diagnóstico',
        'Esperando Repuesto',
        'En Reparación',
        'Listo para Entrega',
    ];

    /**
     * Construye el resumen operativo del tablero Kanban de órdenes activas en el taller.
     *
     * @return array<string, mixed>
     */
    public function getKanbanBoardSummary(): array
    {
        $activeOrders = Schedule::with(['customer', 'vehicle', 'worker'])
            ->where('state', 'Pendiente')
            ->orderByDesc('created_at')
            ->get();

        $columns = [];
        foreach (self::KANBAN_STAGES as $stage) {
            $columns[$stage] = [
                'stage' => $stage,
                'orders_count' => 0,
                'orders' => [],
            ];
        }

        foreach ($activeOrders as $order) {
            $stage = $order->workshop_status ?: 'Recibido';
            if (!isset($columns[$stage])) {
                $stage = 'Recibido';
            }

            $servicesList = $this->normalizeServicesList($order->servicios);

            $columns[$stage]['orders_count']++;
            $columns[$stage]['orders'][] = [
                'id' => $order->id,
                'customer_id' => $order->customer_id,
                'customer_name' => $order->customer->name ?? 'Cliente sin nombre',
                'customer_phone' => $order->customer->phone ?? null,
                'vehicle_id' => $order->vehicle_id,
                'vehicle_plate' => $order->vehicle->plate ?? 'SIN-PLACA',
                'vehicle_make_model' => trim(($order->vehicle->make ?? '') . ' ' . ($order->vehicle->model ?? '')),
                'worker_id' => $order->worker_id,
                'worker_name' => $order->worker->name ?? 'Sin técnico asignado',
                'workshop_status' => $stage,
                'mileage' => $order->mileage !== null ? (int) $order->mileage : null,
                'notes' => $order->notes,
                'services' => $servicesList,
                'created_at' => $order->created_at ? $order->created_at->toIso8601String() : null,
            ];
        }

        return [
            'total_active_orders' => $activeOrders->count(),
            'ready_for_delivery_count' => $columns['Listo para Entrega']['orders_count'] ?? 0,
            'waiting_parts_count' => $columns['Esperando Repuesto']['orders_count'] ?? 0,
            'columns' => array_values($columns),
        ];
    }

    /**
     * Analiza el parque automotor registrado para detectar vencimientos de SOAT,
     * Tecnomecánica y cambios de aceite pendientes o próximos.
     *
     * @param int $daysThreshold Días de anticipación para alertar vencimientos próximos
     * @param int $kmThreshold   Kilómetros de margen antes del cambio de aceite
     * @return array<string, mixed>
     */
    public function getVehicleMaintenanceAlerts(int $daysThreshold = 15, int $kmThreshold = 500): array
    {
        $vehicles = Vehicle::with('customer')->orderBy('plate')->get();
        $today = Carbon::today();
        $upcomingLimit = Carbon::today()->addDays($daysThreshold);

        $alerts = [];
        $counts = [
            'oil_overdue' => 0,
            'oil_soon' => 0,
            'soat_expired' => 0,
            'soat_soon' => 0,
            'tecno_expired' => 0,
            'tecno_soon' => 0,
        ];

        foreach ($vehicles as $vehicle) {
            $vehicleFlags = [];

            // 1. Evaluación de kilometraje para cambio de aceite
            if ($vehicle->current_mileage !== null && $vehicle->next_oil_change_km !== null) {
                $currentKm = (int) $vehicle->current_mileage;
                $nextOilKm = (int) $vehicle->next_oil_change_km;
                $remainingKm = $nextOilKm - $currentKm;

                if ($remainingKm <= 0) {
                    $counts['oil_overdue']++;
                    $vehicleFlags[] = [
                        'type' => 'OIL_OVERDUE',
                        'severity' => 'CRITICAL',
                        'message' => sprintf(
                            'Cambio de aceite vencido por %s km (Actual: %s km / Meta: %s km)',
                            number_format(abs($remainingKm), 0, ',', '.'),
                            number_format($currentKm, 0, ',', '.'),
                            number_format($nextOilKm, 0, ',', '.')
                        ),
                    ];
                } elseif ($remainingKm <= $kmThreshold) {
                    $counts['oil_soon']++;
                    $vehicleFlags[] = [
                        'type' => 'OIL_SOON',
                        'severity' => 'WARNING',
                        'message' => sprintf(
                            'Próximo cambio de aceite en %s km',
                            number_format($remainingKm, 0, ',', '.')
                        ),
                    ];
                }
            }

            // 2. Evaluación de vencimiento de SOAT
            if (!empty($vehicle->soat_due_date)) {
                $soatDate = Carbon::parse($vehicle->soat_due_date)->startOfDay();
                if ($soatDate->lt($today)) {
                    $counts['soat_expired']++;
                    $vehicleFlags[] = [
                        'type' => 'SOAT_EXPIRED',
                        'severity' => 'CRITICAL',
                        'message' => sprintf('SOAT vencido desde el %s', $soatDate->toDateString()),
                    ];
                } elseif ($soatDate->lte($upcomingLimit)) {
                    $counts['soat_soon']++;
                    $vehicleFlags[] = [
                        'type' => 'SOAT_SOON',
                        'severity' => 'WARNING',
                        'message' => sprintf(
                            'SOAT próximo a vencer el %s (%d días)',
                            $soatDate->toDateString(),
                            (int) $today->diffInDays($soatDate)
                        ),
                    ];
                }
            }

            // 3. Evaluación de vencimiento de Tecnomecánica
            if (!empty($vehicle->tecno_due_date)) {
                $tecnoDate = Carbon::parse($vehicle->tecno_due_date)->startOfDay();
                if ($tecnoDate->lt($today)) {
                    $counts['tecno_expired']++;
                    $vehicleFlags[] = [
                        'type' => 'TECNO_EXPIRED',
                        'severity' => 'CRITICAL',
                        'message' => sprintf('Tecnomecánica vencida desde el %s', $tecnoDate->toDateString()),
                    ];
                } elseif ($tecnoDate->lte($upcomingLimit)) {
                    $counts['tecno_soon']++;
                    $vehicleFlags[] = [
                        'type' => 'TECNO_SOON',
                        'severity' => 'WARNING',
                        'message' => sprintf(
                            'Tecnomecánica próxima a vencer el %s (%d días)',
                            $tecnoDate->toDateString(),
                            (int) $today->diffInDays($tecnoDate)
                        ),
                    ];
                }
            }

            if (!empty($vehicleFlags)) {
                $alerts[] = [
                    'vehicle_id' => $vehicle->id,
                    'plate' => $vehicle->plate,
                    'make' => $vehicle->make,
                    'model' => $vehicle->model,
                    'customer_id' => $vehicle->customer_id,
                    'customer_name' => $vehicle->customer->name ?? 'Sin propietario',
                    'customer_phone' => $vehicle->customer->phone ?? null,
                    'current_mileage' => $vehicle->current_mileage,
                    'next_oil_change_km' => $vehicle->next_oil_change_km,
                    'soat_due_date' => $vehicle->soat_due_date,
                    'tecno_due_date' => $vehicle->tecno_due_date,
                    'flags' => $vehicleFlags,
                ];
            }
        }

        return [
            'analyzed_vehicles_count' => $vehicles->count(),
            'vehicles_with_alerts_count' => count($alerts),
            'counts' => $counts,
            'alerts' => $alerts,
        ];
    }

    /**
     * Normaliza el campo JSON/array de servicios de una orden de taller.
     *
     * @param mixed $rawServices
     * @return array<int, string>
     */
    public function normalizeServicesList(mixed $rawServices): array
    {
        if (is_array($rawServices)) {
            return array_values(array_map('strval', $rawServices));
        }

        if (is_string($rawServices) && trim($rawServices) !== '') {
            $decoded = json_decode($rawServices, true);
            if (is_array($decoded)) {
                return array_values(array_map('strval', $decoded));
            }
            return [$rawServices];
        }

        return [];
    }
}
