<?php

namespace App\Console\Commands;

use App\Services\WorkshopKanbanService;
use Illuminate\Console\Command;

/**
 * Comando Artisan para inspeccionar alertas preventivas de vehículos registrados
 * (vencimiento de SOAT, revisión Tecnomecánica y próximo cambio de aceite por kilometraje)
 * junto con el estado actual del tablero Kanban del taller.
 */
class CheckVehicleAlertsCommand extends Command
{
    /**
     * Nombre y firma del comando de consola.
     *
     * @var string
     */
    protected $signature = 'factupos:check-alerts
                            {--days=15 : Días de anticipación para alertar vencimientos de SOAT y Tecnomecánica}
                            {--km=500 : Kilómetros de anticipación para alertar próximo cambio de aceite}
                            {--json : Imprime la salida en formato JSON}';

    /**
     * Descripción del comando de consola.
     *
     * @var string
     */
    protected $description = 'Verifica alertas preventivas de cambio de aceite, SOAT, Tecnomecánica y estado Kanban del taller';

    /**
     * Ejecuta el comando de consola.
     */
    public function handle(WorkshopKanbanService $workshopService): int
    {
        $daysThreshold = (int) $this->option('days');
        $kmThreshold = (int) $this->option('km');

        $alertsReport = $workshopService->getVehicleMaintenanceAlerts($daysThreshold, $kmThreshold);
        $kanbanSummary = $workshopService->getKanbanBoardSummary();

        if ($this->option('json')) {
            $this->line(json_encode([
                'kanban' => $kanbanSummary,
                'vehicle_alerts' => $alertsReport,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        $this->info('=========================================================================');
        $this->info('   FACTUPOS — MONITOR DE ALERTAS DE TALLER, SOAT, TECNO Y KILOMETRAJE    ');
        $this->info('=========================================================================');

        $kanbanRows = array_map(function (array $col) {
            return [
                $col['stage'],
                (string) $col['orders_count'],
            ];
        }, $kanbanSummary['columns']);

        $this->comment('>>> ESTADO ACTUAL DEL TABLERO KANBAN DE TALLER:');
        $this->table(['Etapa del Taller', 'Órdenes Activas'], $kanbanRows);

        $counts = $alertsReport['counts'];
        $this->newLine();
        $this->comment('>>> RESUMEN DE ALERTAS DEL PARQUE AUTOMOTOR (' . $alertsReport['analyzed_vehicles_count'] . ' vehículos):');
        $this->table(
            ['Tipo de Alerta Preventiva', 'Cantidad de Vehículos'],
            [
                ['Cambio de Aceite Vencido (Km actual >= Km próximo cambio)', (string) $counts['oil_overdue']],
                ['Cambio de Aceite Próximo (Dentro de ' . $kmThreshold . ' km)', (string) $counts['oil_soon']],
                ['SOAT Vencido', (string) $counts['soat_expired']],
                ['SOAT Próximo a Vencer (Dentro de ' . $daysThreshold . ' días)', (string) $counts['soat_soon']],
                ['Tecnomecánica Vencida', (string) $counts['tecno_expired']],
                ['Tecnomecánica Próxima a Vencer (Dentro de ' . $daysThreshold . ' días)', (string) $counts['tecno_soon']],
            ]
        );

        if (!empty($alertsReport['alerts'])) {
            $this->newLine();
            $this->comment('>>> DETALLE DE VEHÍCULOS CON ALERTAS ACTIVAS:');

            $alertRows = [];
            foreach ($alertsReport['alerts'] as $item) {
                $messages = implode(' | ', array_column($item['flags'], 'message'));
                $alertRows[] = [
                    $item['plate'],
                    trim($item['make'] . ' ' . $item['model']),
                    $item['customer_name'],
                    $item['customer_phone'] ?? 'Sin teléfono',
                    $messages,
                ];
            }

            $this->table(
                ['Placa', 'Vehículo', 'Propietario', 'Teléfono / WhatsApp', 'Detalle de Alertas'],
                $alertRows
            );
        } else {
            $this->info('Todos los vehículos registrados tienen su mantenimiento y documentación al día.');
        }

        return self::SUCCESS;
    }
}
