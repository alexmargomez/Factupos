<?php

namespace Tests\Unit;

use App\Services\CsvAccountingExportService;
use App\Services\PosBillingService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas unitarias para el servicio de facturación POS (cálculo de pagos mixtos,
 * créditos y saldos pendientes) y generación de archivos CSV con BOM UTF-8.
 */
class InventoryValuationCalculationsUnitTest extends TestCase
{
    public function test_pos_billing_service_normalizes_cash_and_credit_payment_breakdowns(): void
    {
        $billingService = new PosBillingService();

        // 1. Venta de contado en efectivo
        $cashBreakdown = $billingService->normalizePaymentBreakdown(120000, 'Efectivo');
        $this->assertSame(120000, $cashBreakdown['total']);
        $this->assertSame(120000, $cashBreakdown['paid_amount']);
        $this->assertSame(0, $cashBreakdown['pending_balance']);
        $this->assertSame('Pagado', $cashBreakdown['payment_status']);

        // 2. Venta a crédito sin abono inicial
        $creditBreakdown = $billingService->normalizePaymentBreakdown(180000, 'Crédito / Pendiente', 0);
        $this->assertSame(0, $creditBreakdown['paid_amount']);
        $this->assertSame(180000, $creditBreakdown['pending_balance']);
        $this->assertSame('Pendiente', $creditBreakdown['payment_status']);

        // 3. Venta a crédito con abono parcial de $60.000
        $partialBreakdown = $billingService->normalizePaymentBreakdown(
            180000,
            'Crédito / Pendiente',
            60000,
            'Abono inicial por Nequi'
        );
        $this->assertSame(60000, $partialBreakdown['paid_amount']);
        $this->assertSame(120000, $partialBreakdown['pending_balance']);
        $this->assertSame('Parcial', $partialBreakdown['payment_status']);
        $this->assertSame('Abono inicial por Nequi', $partialBreakdown['payment_notes']);
    }

    public function test_pos_billing_service_rejects_negative_invoice_totals(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $billingService = new PosBillingService();
        $billingService->normalizePaymentBreakdown(-5000, 'Efectivo');
    }

    public function test_csv_accounting_export_service_prefixes_utf8_bom_and_escapes_semicolon_columns(): void
    {
        $csvService = new CsvAccountingExportService();

        $csv = $csvService->encodeRowsToCsv(
            ['SKU', 'Producto', 'Precio ($ COP)'],
            [
                ['LUB-001', 'Aceite "Sintético" 10W40', 68000],
            ]
        );

        $this->assertStringStartsWith(CsvAccountingExportService::UTF8_BOM, $csv);
        $this->assertStringContainsString('"SKU";"Producto";"Precio ($ COP)"', $csv);
        $this->assertStringContainsString('"LUB-001";"Aceite ""Sintético"" 10W40";"68000"', $csv);
    }
}
