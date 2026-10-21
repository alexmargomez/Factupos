<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\WorkerSettlement;
use Carbon\Carbon;

/**
 * Servicio de exportación contable en formato CSV compatible con Microsoft Excel
 * (incluye BOM UTF-8 y delimitador punto y coma `;` para configuración regional en español).
 */
class CsvAccountingExportService
{
    public const UTF8_BOM = "\xEF\xBB\xBF";

    /**
     * Genera el contenido CSV de ventas, costos de repuestos, mano de obra y cartera.
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @return string
     */
    public function generateSalesAccountingCsv(?string $startDate = null, ?string $endDate = null): string
    {
        $query = Sale::with(['customer', 'vehicle', 'worker', 'invoice', 'saleDetails.product'])
            ->orderByDesc('created_at');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ]);
        }

        $sales = $query->get();

        $headers = [
            'ID Venta',
            'Número Factura',
            'Fecha Emisión',
            'Cliente',
            'Teléfono Cliente',
            'Placa Vehículo',
            'Mecánico Asignado',
            'Método de Pago',
            'Estado de Pago',
            'Total Factura ($ COP)',
            'Monto Pagado ($ COP)',
            'Saldo Pendiente ($ COP)',
            'Notas de Pago',
        ];

        $rows = [];
        foreach ($sales as $sale) {
            $rows[] = [
                $sale->id,
                $sale->invoice->invoice_number ?? sprintf('FACT-%06d', $sale->id),
                $sale->created_at ? $sale->created_at->format('Y-m-d H:i') : '',
                $sale->customer->name ?? 'Cliente General',
                $sale->customer->phone ?? '',
                $sale->vehicle->plate ?? 'Mostrador',
                $sale->worker->name ?? 'General',
                $sale->payment_method ?: 'Efectivo',
                $sale->payment_status ?: 'Pagado',
                (int) $sale->total,
                (int) ($sale->paid_amount ?? $sale->total ?? 0),
                (int) ($sale->pending_balance ?? 0),
                $sale->payment_notes ?? '',
            ];
        }

        return $this->encodeRowsToCsv($headers, $rows);
    }

    /**
     * Genera el contenido CSV del inventario valorizado (costo de compra, precio de venta, margen y proveedor).
     *
     * @return string
     */
    public function generateInventoryValuationCsv(): string
    {
        $products = Product::with(['inventory', 'supplier', 'category'])
            ->orderBy('name')
            ->get();

        $headers = [
            'ID Producto',
            'SKU / Código de Barras',
            'Nombre del Repuesto / Producto',
            'Categoría',
            'Proveedor',
            'Stock Actual',
            'Stock Mínimo Alerta',
            'Costo Compra Unitario ($ COP)',
            'Precio Venta Unitario ($ COP)',
            'Utilidad Unitaria ($ COP)',
            'Margen Comercial (%)',
            'Capital en Bodega al Costo ($ COP)',
            'Valor Bodega a Precio Venta ($ COP)',
        ];

        $rows = [];
        foreach ($products as $product) {
            $stock = max(0, (int) ($product->inventory->quantity ?? 0));
            $cost = (int) ($product->cost_price ?? 0);
            $price = (int) ($product->price ?? 0);
            $profit = $price - $cost;
            $margin = $price > 0 ? round(($profit / $price) * 100, 2) : 0.0;

            $rows[] = [
                $product->id,
                $product->sku ?: sprintf('PROD-%04d', $product->id),
                $product->name,
                $product->category->name ?? 'General',
                $product->supplier->name ?? 'Sin proveedor',
                $stock,
                (int) ($product->min_stock ?? 5),
                $cost,
                $price,
                $profit,
                $margin . '%',
                $stock * $cost,
                $stock * $price,
            ];
        }

        return $this->encodeRowsToCsv($headers, $rows);
    }

    /**
     * Genera el contenido CSV del historial de liquidaciones pagadas a mecánicos.
     *
     * @return string
     */
    public function generateWorkerSettlementsCsv(): string
    {
        $settlements = WorkerSettlement::with('worker')
            ->orderByDesc('settled_at')
            ->get();

        $headers = [
            'ID Liquidación',
            'Fecha y Hora de Pago',
            'Mecánico / Técnico',
            'Mano de Obra Bruta ($ COP)',
            'Porcentaje Técnico (%)',
            'Pago al Técnico ($ COP)',
            'Utilidad Taller ($ COP)',
            'Observaciones',
        ];

        $rows = [];
        foreach ($settlements as $settlement) {
            $rows[] = [
                $settlement->id,
                $settlement->settled_at ? $settlement->settled_at->format('Y-m-d H:i') : '',
                $settlement->worker->name ?? sprintf('Técnico #%d', $settlement->worker_id),
                (int) $settlement->gross_amount,
                (int) $settlement->commission_percentage . '%',
                (int) $settlement->commission_amount,
                (int) $settlement->workshop_amount,
                $settlement->notes ?? '',
            ];
        }

        return $this->encodeRowsToCsv($headers, $rows);
    }

    /**
     * Codifica cabeceras y filas en una cadena CSV con BOM UTF-8 y separador `;`.
     *
     * @param array<int, string>             $headers
     * @param array<int, array<int, mixed>> $rows
     * @return string
     */
    public function encodeRowsToCsv(array $headers, array $rows): string
    {
        $escape = static function (mixed $value): string {
            $str = $value === null ? '' : (string) $value;
            return '"' . str_replace('"', '""', $str) . '"';
        };

        $lines = [implode(';', array_map($escape, $headers))];
        foreach ($rows as $row) {
            $lines[] = implode(';', array_map($escape, $row));
        }

        return self::UTF8_BOM . implode("\r\n", $lines);
    }
}
