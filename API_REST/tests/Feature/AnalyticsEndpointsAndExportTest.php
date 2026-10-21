<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Worker;
use App\Services\CsvAccountingExportService;
use App\Services\PosBillingService;
use App\Services\WorkerCommissionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pruebas integrales para el endpoint consolidado de analítica (/api/analytics/summary),
 * exportación contable CSV de ventas, inventario valorizado y liquidaciones,
 * y generación de enlaces profundos de WhatsApp para facturas y cotizaciones.
 */
class AnalyticsEndpointsAndExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create([
            'username' => 'admin_analytics_test',
            'role' => 'Admin',
        ]));
    }

    public function test_analytics_summary_endpoint_aggregates_financial_inventory_kanban_and_commissions(): void
    {
        $supplier = Supplier::create([
            'name' => 'Mayorista Repuestos Test',
            'nit' => '900.800.700-1',
            'phone' => '3109990000',
        ]);

        $product = Product::create([
            'sku' => 'ANA-SKU-100',
            'name' => 'Kit de Embrague Original',
            'cost_price' => 110000,
            'price' => 165000,
            'min_stock' => 4,
            'supplier_id' => $supplier->id,
        ]);

        Inventory::create([
            'product_id' => $product->id,
            'quantity' => 8,
        ]);

        $customer = Customer::create([
            'name' => 'Cliente Ejecutivo Analítica',
            'phone' => '3152223344',
            'email' => 'ejecutivo@example.com',
        ]);

        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate' => 'ANA777',
            'make' => 'Yamaha',
            'model' => 'XTZ 250',
            'current_mileage' => 22000,
            'next_oil_change_km' => 21500,
            'soat_due_date' => Carbon::yesterday()->toDateString(),
        ]);

        $worker = Worker::create([
            'name' => 'Técnico Analítica',
            'totalDia' => 80000,
            'commission_percentage' => 50,
        ]);

        Schedule::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'worker_id' => $worker->id,
            'state' => 'Pendiente',
            'workshop_status' => 'Listo para Entrega',
            'mileage' => 22000,
            'notes' => 'Listo para pasar a caja',
            'servicios' => json_encode(['Cambio de kit de embrague']),
        ]);

        $sale = Sale::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'worker_id' => $worker->id,
            'total' => 245000,
            'payment_method' => 'Pago Mixto',
            'payment_status' => 'Parcial',
            'paid_amount' => 200000,
            'pending_balance' => 45000,
            'payment_notes' => 'Efectivo $100.000 + Nequi $100.000',
        ]);

        SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'subtotal' => 165000,
        ]);

        Service::create([
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'worker_id' => $worker->id,
            'service' => 'Instalación de kit de embrague',
            'price_service' => 80000,
        ]);

        Expense::create([
            'description' => 'Compra de empaque de tapa embrague adicional',
            'category' => 'Insumos Taller',
            'amount' => 12000,
            'payment_method' => 'Efectivo',
            'expense_date' => Carbon::now(),
        ]);

        $response = $this->getJson('/api/analytics/summary');

        $response->assertStatus(200)
            ->assertJsonPath('financial.kpis.total_invoiced', 245000)
            ->assertJsonPath('financial.kpis.total_collected', 200000)
            ->assertJsonPath('financial.kpis.total_pending_receivables', 45000)
            ->assertJsonPath('financial.kpis.parts_gross_profit', 55000)
            ->assertJsonPath('financial.kpis.labor_revenue', 80000)
            ->assertJsonPath('inventory_valuation.summary.total_skus', 1)
            ->assertJsonPath('workshop_kanban.total_active_orders', 1)
            ->assertJsonPath('workshop_kanban.ready_for_delivery_count', 1)
            ->assertJsonPath('vehicle_alerts.vehicles_with_alerts_count', 1)
            ->assertJsonPath('worker_commissions.total_gross_daily_labor', 80000)
            ->assertJsonPath('worker_commissions.total_pending_mechanic_payout', 40000);
    }

    public function test_csv_export_and_whatsapp_builder_services_generate_valid_outputs(): void
    {
        $customer = Customer::create([
            'name' => 'Laura Victoria Pineda',
            'phone' => '3005556677',
            'email' => 'laura.pineda@example.com',
        ]);

        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate' => 'WSP123',
            'make' => 'Suzuki',
            'model' => 'Gixxer SF 250',
        ]);

        $worker = Worker::create([
            'name' => 'Técnico Liquidación CSV',
            'totalDia' => 100000,
            'commission_percentage' => 60,
        ]);

        $sale = Sale::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'worker_id' => $worker->id,
            'total' => 100000,
            'payment_method' => 'Efectivo',
            'payment_status' => 'Pagado',
            'paid_amount' => 100000,
            'pending_balance' => 0,
        ]);

        /** @var WorkerCommissionService $commissionService */
        $commissionService = $this->app->make(WorkerCommissionService::class);
        $settlement = $commissionService->settleWorkerBalance($worker, 'Pago de comisión en efectivo');

        $this->assertSame(60000, (int) $settlement->commission_amount);
        $this->assertSame(40000, (int) $settlement->workshop_amount);

        /** @var CsvAccountingExportService $csvService */
        $csvService = $this->app->make(CsvAccountingExportService::class);

        $salesCsv = $csvService->generateSalesAccountingCsv();
        $this->assertStringContainsString('Laura Victoria Pineda', $salesCsv);
        $this->assertStringContainsString('WSP123', $salesCsv);

        $settlementsCsv = $csvService->generateWorkerSettlementsCsv();
        $this->assertStringContainsString('Técnico Liquidación CSV', $settlementsCsv);
        $this->assertStringContainsString('60000', $settlementsCsv);

        /** @var PosBillingService $posBillingService */
        $posBillingService = $this->app->make(PosBillingService::class);
        $whatsappPayload = $posBillingService->buildWhatsAppInvoicePayload($sale, false);

        $this->assertSame('573005556677', $whatsappPayload['phone_formatted']);
        $this->assertStringContainsString('https://wa.me/573005556677?text=', $whatsappPayload['whatsapp_url']);
        $this->assertStringContainsString('WSP123', $whatsappPayload['message']);
    }
}
