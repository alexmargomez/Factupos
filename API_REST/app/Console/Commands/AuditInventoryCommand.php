<?php

namespace App\Console\Commands;

use App\Services\InventoryValuationService;
use Illuminate\Console\Command;

/**
 * Comando Artisan para auditar el inventario valorizado, verificar márgenes
 * de comercialización y listar repuestos en alerta de agotamiento (stock mínimo).
 */
class AuditInventoryCommand extends Command
{
    /**
     * Nombre y firma del comando de consola.
     *
     * @var string
     */
    protected $signature = 'factupos:audit-inventory
                            {--only-alerts : Muestra únicamente los productos con stock agotado o bajo el mínimo}
                            {--json : Imprime la salida en formato JSON estructurado}';

    /**
     * Descripción del comando de consola.
     *
     * @var string
     */
    protected $description = 'Audita la valorización de inventario en bodega (costo vs venta), márgenes y alertas de stock mínimo';

    /**
     * Ejecuta el comando de consola.
     */
    public function handle(InventoryValuationService $valuationService): int
    {
        $report = $valuationService->getWarehouseValuationReport();

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return self::SUCCESS;
        }

        $summary = $report['summary'];

        $this->info('=========================================================================');
        $this->info('   FACTUPOS — AUDITORÍA DE INVENTARIO VALORIZADO Y BODEGA DE REPUESTOS   ');
        $this->info('=========================================================================');

        $this->table(
            ['Indicador de Bodega', 'Valor Consolidado'],
            [
                ['Total Referencias (SKUs)', number_format($summary['total_skus'], 0, ',', '.')],
                ['Total Unidades Físicas en Stock', number_format($summary['total_units_in_stock'], 0, ',', '.')],
                ['Capital Invertido al Costo ($ COP)', '$ ' . number_format($summary['total_capital_at_cost'], 0, ',', '.')],
                ['Valor Potencial de Venta ($ COP)', '$ ' . number_format($summary['total_potential_revenue'], 0, ',', '.')],
                ['Utilidad Bruta Proyectada ($ COP)', '$ ' . number_format($summary['total_projected_profit'], 0, ',', '.')],
                ['Margen Ponderado de Bodega (%)', $summary['weighted_margin_pct'] . ' %'],
                ['Referencias en Estado Óptimo', (string) $summary['healthy_stock_count']],
                ['Referencias Bajo Stock Mínimo', (string) $summary['low_stock_count']],
                ['Referencias Agotadas (Stock <= 0)', (string) $summary['out_of_stock_count']],
            ]
        );

        $itemsToDisplay = $this->option('only-alerts')
            ? $report['reorder_alerts']
            : $report['items'];

        if (empty($itemsToDisplay)) {
            $this->info('No se encontraron productos pendientes de reabastecimiento.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->comment($this->option('only-alerts')
            ? '>>> LISTADO DE PRODUCTOS EN ALERTA DE REABASTECIMIENTO:'
            : '>>> DETALLE DE PRODUCTOS Y VALORIZACIÓN UNITARIA:');

        $rows = array_map(function (array $item) {
            return [
                $item['sku'],
                mb_strimwidth($item['name'], 0, 32, '...'),
                mb_strimwidth($item['supplier_name'], 0, 22, '...'),
                $item['stock'] . ' / min ' . $item['min_stock'],
                $item['stock_status'],
                '$ ' . number_format($item['cost_price'], 0, ',', '.'),
                '$ ' . number_format($item['sale_price'], 0, ',', '.'),
                $item['margin_pct'] . '%',
                '$ ' . number_format($item['total_cost_value'], 0, ',', '.'),
            ];
        }, $itemsToDisplay);

        $this->table(
            ['SKU', 'Producto', 'Proveedor', 'Stock', 'Estado', 'Costo', 'Venta', 'Margen', 'Capital Total'],
            $rows
        );

        return self::SUCCESS;
    }
}
