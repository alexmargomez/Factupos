<?php

namespace App\Console\Commands;

use App\Services\FinancialReportService;
use App\Services\WorkerCommissionService;
use Illuminate\Console\Command;

/**
 * Comando Artisan para generar el resumen de arqueo de caja, utilidad neta real,
 * cuentas por cobrar (cartera) y liquidaciones de técnicos desde la terminal.
 */
class CashboxCloseSummaryCommand extends Command
{
    /**
     * Nombre y firma del comando de consola.
     *
     * @var string
     */
    protected $signature = 'factupos:cashbox-summary
                            {--from= : Fecha inicial en formato YYYY-MM-DD}
                            {--to= : Fecha final en formato YYYY-MM-DD}
                            {--json : Imprime el reporte en formato JSON}';

    /**
     * Descripción del comando de consola.
     *
     * @var string
     */
    protected $description = 'Genera el informe de cierre de caja, utilidad neta, cartera pendiente y comisiones de mecánicos';

    /**
     * Ejecuta el comando de consola.
     */
    public function handle(
        FinancialReportService $financialService,
        WorkerCommissionService $commissionService
    ): int {
        $startDate = $this->option('from');
        $endDate = $this->option('to');

        $financial = $financialService->buildPeriodSummary($startDate, $endDate);
        $commissions = $commissionService->getPendingCommissionsOverview();

        if ($this->option('json')) {
            $this->line(json_encode([
                'financial' => $financial,
                'pending_commissions' => $commissions,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        $kpis = $financial['kpis'];

        $this->info('=========================================================================');
        $this->info('   FACTUPOS — ARQUEO DE CAJA, UTILIDAD NETA Y ESTADO DE CARTERA POS      ');
        $this->info('=========================================================================');
        $this->line(sprintf(
            'Período analizado: %s hasta %s',
            $financial['period']['start_date'],
            $financial['period']['end_date']
        ));
        $this->newLine();

        $this->table(
            ['Indicador Financiero / Contable', 'Monto ($ COP)'],
            [
                ['Cantidad de Facturas Emitidas', (string) $kpis['sales_count']],
                ['Total Facturado Bruto', '$ ' . number_format($kpis['total_invoiced'], 0, ',', '.')],
                ['Total Recaudado en Caja / Bancos', '$ ' . number_format($kpis['total_collected'], 0, ',', '.')],
                ['Cartera Pendiente por Cobrar (Créditos)', '$ ' . number_format($kpis['total_pending_receivables'], 0, ',', '.')],
                ['Eficiencia de Recaudo (%)', $kpis['collection_efficiency_pct'] . ' %'],
                ['Ingresos por Repuestos Vendidos', '$ ' . number_format($kpis['parts_revenue'], 0, ',', '.')],
                ['Costo de Adquisición de Repuestos Vendidos', '$ ' . number_format($kpis['parts_cost'], 0, ',', '.')],
                ['Utilidad Bruta en Repuestos', '$ ' . number_format($kpis['parts_gross_profit'], 0, ',', '.')],
                ['Ingresos por Mano de Obra (Taller)', '$ ' . number_format($kpis['labor_revenue'], 0, ',', '.')],
                ['Gastos Menores y Salidas de Caja', '- $ ' . number_format($kpis['total_expenses'], 0, ',', '.')],
                ['Liquidaciones Pagadas a Mecánicos', '- $ ' . number_format($kpis['total_worker_payouts'], 0, ',', '.')],
                ['Flujo Neto Operativo de Caja', '$ ' . number_format($kpis['net_operating_cash'], 0, ',', '.')],
                ['Utilidad Neta Real del Período', '$ ' . number_format($kpis['real_net_profit'], 0, ',', '.')],
            ]
        );

        if (!empty($financial['payment_methods_breakdown'])) {
            $this->newLine();
            $this->comment('>>> DESGLOSE POR MÉTODO DE PAGO:');
            $methodRows = array_map(function (array $row) {
                return [
                    $row['payment_method'],
                    (string) $row['transactions_count'],
                    '$ ' . number_format($row['total_invoiced'], 0, ',', '.'),
                    '$ ' . number_format($row['total_collected'], 0, ',', '.'),
                    '$ ' . number_format($row['total_pending'], 0, ',', '.'),
                ];
            }, $financial['payment_methods_breakdown']);

            $this->table(
                ['Método de Pago', 'Facturas', 'Facturado', 'Recaudado', 'Saldo Pendiente'],
                $methodRows
            );
        }

        if (!empty($commissions['workers'])) {
            $this->newLine();
            $this->comment('>>> ACUMULADO ACTUAL DE MECÁNICOS PENDIENTE POR LIQUIDAR:');
            $workerRows = array_map(function (array $w) {
                return [
                    $w['name'],
                    $w['commission_percentage'] . '% Técnico / ' . $w['workshop_percentage'] . '% Taller',
                    '$ ' . number_format($w['gross_daily_labor'], 0, ',', '.'),
                    '$ ' . number_format($w['pending_mechanic_payout'], 0, ',', '.'),
                    '$ ' . number_format($w['pending_workshop_share'], 0, ',', '.'),
                ];
            }, $commissions['workers']);

            $this->table(
                ['Mecánico', 'Esquema Comisión', 'Bruto Día', 'Pago al Técnico', 'Utilidad Taller'],
                $workerRows
            );
        }

        return self::SUCCESS;
    }
}
