<?php

namespace App\Console\Commands;

use App\Services\CsvAccountingExportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Comando Artisan para generar y exportar archivos contables CSV compatibles con Excel
 * (Ventas y Cartera, Inventario Valorizado y Liquidaciones de Mecánicos) directamente
 * al directorio storage/app/exports o a la salida estándar.
 */
class ExportAccountingCsvCommand extends Command
{
    /**
     * Nombre y firma del comando de consola.
     *
     * @var string
     */
    protected $signature = 'factupos:export-csv
                            {--type=all : Tipo de reporte a exportar: sales, inventory, settlements o all}
                            {--from= : Fecha inicial YYYY-MM-DD para el reporte de ventas}
                            {--to= : Fecha final YYYY-MM-DD para el reporte de ventas}
                            {--stdout : Imprime el contenido CSV en consola en lugar de guardarlo en disco}';

    /**
     * Descripción del comando de consola.
     *
     * @var string
     */
    protected $description = 'Exporta reportes contables en formato CSV (UTF-8 BOM compatible con Excel) para ventas, inventario valorizado y liquidaciones';

    /**
     * Ejecuta el comando de consola.
     */
    public function handle(CsvAccountingExportService $exportService): int
    {
        $type = strtolower((string) $this->option('type'));
        $from = $this->option('from');
        $to = $this->option('to');
        $stdout = (bool) $this->option('stdout');

        $allowedTypes = ['sales', 'inventory', 'settlements', 'all'];
        if (!in_array($type, $allowedTypes, true)) {
            $this->error('El parámetro --type debe ser uno de: sales, inventory, settlements, all.');
            return self::FAILURE;
        }

        $dateStamp = Carbon::now()->format('Ymd_His');
        $exportDir = storage_path('app/exports');

        if (!$stdout && !File::isDirectory($exportDir)) {
            File::makeDirectory($exportDir, 0755, true);
        }

        $generatedFiles = [];

        if ($type === 'sales' || $type === 'all') {
            $csv = $exportService->generateSalesAccountingCsv($from, $to);
            if ($stdout) {
                $this->line($csv);
            } else {
                $filePath = $exportDir . "/FactuPOS_Ventas_Cartera_{$dateStamp}.csv";
                File::put($filePath, $csv);
                $generatedFiles[] = ['Ventas y Cartera POS', $filePath, strlen($csv) . ' bytes'];
            }
        }

        if ($type === 'inventory' || $type === 'all') {
            $csv = $exportService->generateInventoryValuationCsv();
            if ($stdout) {
                $this->line($csv);
            } else {
                $filePath = $exportDir . "/FactuPOS_Inventario_Valorizado_{$dateStamp}.csv";
                File::put($filePath, $csv);
                $generatedFiles[] = ['Inventario Valorizado (Costo vs Venta)', $filePath, strlen($csv) . ' bytes'];
            }
        }

        if ($type === 'settlements' || $type === 'all') {
            $csv = $exportService->generateWorkerSettlementsCsv();
            if ($stdout) {
                $this->line($csv);
            } else {
                $filePath = $exportDir . "/FactuPOS_Liquidaciones_Mecanicos_{$dateStamp}.csv";
                File::put($filePath, $csv);
                $generatedFiles[] = ['Historial Liquidaciones Mecánicos', $filePath, strlen($csv) . ' bytes'];
            }
        }

        if (!$stdout) {
            $this->info('Exportación contable CSV completada exitosamente:');
            $this->table(['Reporte Contable', 'Ruta del Archivo Generado', 'Tamaño'], $generatedFiles);
        }

        return self::SUCCESS;
    }
}
