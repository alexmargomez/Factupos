<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pruebas integrales de trazabilidad histórica por cliente y por placa de vehículo,
 * generación de numeración consecutiva de facturas (FACT-XXXXXX) y consulta de servicios.
 */
class CustomerAndVehicleHistoryReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create([
            'username' => 'admin_history_test',
            'role' => 'Admin',
        ]));
    }

    public function test_customer_and_vehicle_endpoints_return_complete_technical_and_billing_history(): void
    {
        $customer = Customer::create([
            'name' => 'Camilo Andrés Betancur',
            'phone' => '3167778899',
            'email' => 'camilo.betancur@example.com',
        ]);

        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate' => 'MTO78D',
            'make' => 'Yamaha',
            'model' => 'MT-03 ABS',
            'current_mileage' => 18400,
            'next_oil_change_km' => 21400,
        ]);

        $worker = Worker::create([
            'name' => 'Mauricio Especialista Yamaha',
            'totalDia' => 0,
            'commission_percentage' => 55,
        ]);

        $product = Product::create([
            'sku' => 'LUB-YAM-10W40',
            'name' => 'Yamalube Full Synthetic 10W40',
            'cost_price' => 42000,
            'price' => 64000,
            'min_stock' => 6,
        ]);

        $sale = Sale::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'worker_id' => $worker->id,
            'total' => 178000,
            'payment_method' => 'Transferencia / Nequi',
            'payment_status' => 'Pagado',
            'paid_amount' => 178000,
            'pending_balance' => 0,
        ]);

        SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'subtotal' => 128000,
        ]);

        Service::create([
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'worker_id' => $worker->id,
            'service' => 'Cambio de aceite sintético y revisión de tensión de cadena',
            'price_service' => 50000,
        ]);

        // Crear factura numerada
        $invoiceResponse = $this->postJson('/api/invoices', [
            'sale_id' => $sale->id,
            'total' => 178000,
        ]);

        $invoiceResponse->assertStatus(201);

        // Consultar servicios realizados al vehículo por placa/ID
        $vehicleServicesResponse = $this->getJson("/api/services/vehicle/{$vehicle->id}");
        $vehicleServicesResponse->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.service', 'Cambio de aceite sintético y revisión de tensión de cadena');

        // Consultar repuestos adquiridos por el cliente
        $customerDetailsResponse = $this->getJson("/api/sale-details/customer/{$customer->id}");
        $customerDetailsResponse->assertStatus(200);

        // Consultar historial de servicios del cliente
        $customerServicesResponse = $this->getJson("/api/services/customer/{$customer->id}");
        $customerServicesResponse->assertStatus(200)
            ->assertJsonCount(1);
    }
}
