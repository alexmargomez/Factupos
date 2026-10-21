<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Service;
use App\Models\Supplier;
use App\Models\Vehicle;
use App\Models\Worker;
use App\Services\CustomerLoyaltyAndHistoryService;
use Database\Seeders\WorkshopCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas integrales para el servicio de expediente técnico/comercial de clientes,
 * ranking de repuestos más vendidos, productividad de mecánicos y ejecución
 * idempotente del WorkshopCatalogSeeder.
 */
class CustomerLoyaltyAndTopProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_dossier_consolidates_lifetime_value_vehicles_and_technical_services(): void
    {
        $customer = Customer::create([
            'name' => 'Ingeniero Alejandro Martínez',
            'phone' => '3108889900',
            'email' => 'alejandro.martinez@example.com',
        ]);

        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate' => 'KAW650',
            'make' => 'Kawasaki',
            'model' => 'Versys 650',
            'current_mileage' => 31000,
            'next_oil_change_km' => 35000,
        ]);

        $worker = Worker::create([
            'name' => 'Especialista Alto Cilindraje',
            'totalDia' => 95000,
            'commission_percentage' => 60,
        ]);

        $supplier = Supplier::create([
            'name' => 'Kawasaki Parts Colombia',
            'nit' => '900.777.666-5',
        ]);

        $product = Product::create([
            'sku' => 'KAW-FIL-001',
            'name' => 'Filtro de Aceite Original Kawasaki',
            'cost_price' => 38000,
            'price' => 62000,
            'min_stock' => 4,
            'supplier_id' => $supplier->id,
        ]);

        $sale = Sale::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'worker_id' => $worker->id,
            'total' => 219000,
            'payment_method' => 'Crédito / Pendiente',
            'payment_status' => 'Parcial',
            'paid_amount' => 150000,
            'pending_balance' => 69000,
        ]);

        SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'subtotal' => 124000,
        ]);

        Service::create([
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'worker_id' => $worker->id,
            'service' => 'Mantenimiento preventivo general de 30.000 km',
            'price_service' => 95000,
        ]);

        /** @var CustomerLoyaltyAndHistoryService $loyaltyService */
        $loyaltyService = $this->app->make(CustomerLoyaltyAndHistoryService::class);

        $dossier = $loyaltyService->buildCustomerDossier($customer->id);

        $this->assertSame('Ingeniero Alejandro Martínez', $dossier['customer']['name']);
        $this->assertSame(1, $dossier['financial_summary']['visits_count']);
        $this->assertSame(219000, $dossier['financial_summary']['lifetime_invoiced_value']);
        $this->assertSame(150000, $dossier['financial_summary']['lifetime_paid_value']);
        $this->assertSame(69000, $dossier['financial_summary']['current_pending_balance']);
        $this->assertTrue($dossier['financial_summary']['has_active_debt']);
        $this->assertSame(2, $dossier['purchased_parts_summary']['total_units_bought']);
        $this->assertCount(1, $dossier['technical_services_history']);

        $topProducts = $loyaltyService->getTopSellingProductsRanking(5);
        $this->assertCount(1, $topProducts);
        $this->assertSame('KAW-FIL-001', $topProducts[0]['sku']);
        $this->assertSame(2, $topProducts[0]['units_sold']);
        $this->assertSame(48000, $topProducts[0]['total_gross_profit']);

        $workerRanking = $loyaltyService->getWorkerProductivityRanking();
        $this->assertCount(1, $workerRanking);
        $this->assertSame(95000, $workerRanking[0]['historical_labor_generated']);
    }

    public function test_workshop_catalog_seeder_populates_categories_suppliers_workers_and_valued_products(): void
    {
        $this->seed(WorkshopCatalogSeeder::class);

        $this->assertDatabaseHas('Suppliers', [
            'name' => 'Distribuidora Nacional de Repuestos S.A.S.',
        ]);

        $this->assertDatabaseHas('Products', [
            'sku' => 'LUB-MOT-20W50',
            'cost_price' => 31000,
            'price' => 45000,
        ]);

        $this->assertDatabaseHas('Workers', [
            'name' => 'Mauricio Hernández (Jefe de Taller)',
            'commission_percentage' => 60,
        ]);
    }
}
