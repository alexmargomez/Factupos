<?php

namespace App\Console\Commands;

use App\Services\WorkerCommissionService;
use Illuminate\Console\Command;

/**
 * Comando Artisan para consultar el historial de liquidaciones pagadas a mecánicos
 * y el consolidado de participación entre técnicos y taller.
 */
class MechanicsSettlementReportCommand extends Command
{
    /**
     * Nombre y firma del comando de consola.
     *
     * @var string
     */
    protected $signature = 'factupos:mechanics-report
                            {--from= : Fecha inicial en formato YYYY-MM-DD}
                            {--to= : Fecha final en formato YYYY-MM-DD}
                            {--json : Imprime el reporte en formato JSON}';

    /**
     * Descripción del comando de consola.
     *
     * @var string
     */
    protected $description = 'Genera el reporte auditable de comisiones y liquidaciones pagadas a los mecánicos del taller';

    /**
     * Ejecuta el comando de consola.
     */
    public function handle(WorkerCommissionService $commissionService): int
    {
        $history = $commissionService->getSettlementsHistoryReport(
            $this->option('from'),
            $this->option('to')
        );
        $pending = $commissionService->getPendingCommissionsOverview();

        if ($this->option('json')) {
            $this->line(json_encode([
                'pending_overview' => $pending,
                'settled_history' => $history,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        $this->info('=========================================================================');
        $this->info('   FACTUPOS — REPORTE DE COMISIONES Y LIQUIDACIONES DE MECÁNICOS         ');
        $this->info('=========================================================================');

        $this->table(
            ['Métrica de Nómina Técnica', 'Valor Consolidado'],
            [
                ['Técnicos Registrados', (string) $pending['workers_count']],
                ['Mano de Obra del Turno Actual (Sin Liquidar)', '$ ' . number_format($pending['total_gross_daily_labor'], 0, ',', '.')],
                ['Comisión Pendiente para Técnicos (Turno Actual)', '$ ' . number_format($pending['total_pending_mechanic_payout'], 0, ',', '.')],
                ['Total Liquidaciones Históricas Registradas', (string) $history['settlements_count']],
                ['Total Mano de Obra Liquidada Histórica', '$ ' . number_format($history['total_gross_settled'], 0, ',', '.')],
                ['Total Pagado a Técnicos (Histórico)', '$ ' . number_format($history['total_paid_to_mechanics'], 0, ',', '.')],
                ['Total Retenido por el Taller (Histórico)', '$ ' . number_format($history['total_retained_by_workshop'], 0, ',', '.')],
            ]
        );

        if (!empty($history['by_worker'])) {
            $this->newLine();
            $this->comment('>>> CONSOLIDADO HISTÓRICO DE PAGOS POR MECÁNICO:');
            $rows = array_map(function (array $row) {
                return [
                    $row['worker_name'],
                    (string) $row['settlements_count'],
                    '$ ' . number_format($row['gross_total'], 0, ',', '.'),
                    '$ ' . number_format($row['commission_paid_total'], 0, ',', '.'),
                    '$ ' . number_format($row['workshop_retained_total'], 0, ',', '.'),
                ];
            }, $history['by_worker']);

            $this->table(
                ['Mecánico', 'Cierres Pagados', 'Bruto Generado', 'Total Pagado Técnico', 'Total Utilidad Taller'],
                $rows
            );
        }

        return self::SUCCESS;
    }
}
