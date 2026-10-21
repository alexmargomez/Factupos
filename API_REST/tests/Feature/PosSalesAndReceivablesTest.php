<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pruebas integrales de facturación en Punto de Venta (POS), descuento transaccional
 * de stock, pagos mixtos, ventas a crédito y registro de abonos sobre cartera pendiente.
 */
class PosSalesAndReceivablesTest extends TestCase
{
    use RefreshDatabase;

    protected User $authenticatedUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticatedUser = User::factory()->create([
            'username' => 'admin_pos_test',
            'role' => 'Admin',
        ]);
        Sanctum::actingAs($this->authenticatedUser);
    }

    public function test_pos_registers_cash_sale_with_fully_paid_status_and_zero_pending_balance(): void
    {
        $customer = Customer::create([
            'name' => 'Juan Carlos Echeverri',
            'phone' => '3105559988',
            'email' => 'juan.echeverri@example.com',
        ]);

        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate' => 'KLM456',
            'make' => 'Yamaha',
            'model' => 'NMAX Connected',
            'current_mileage' => 14200,
            'next_oil_change_km' => 17000,
        ]);

        $worker = Worker::create([
            'name' => 'Carlos Arturo Restrepo',
            'totalDia' => 0,
            'commission_percentage' => 55,
        ]);

        $response = $this->postJson('/api/sales', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'worker_id' => $worker->id,
            'total' => 135000,
            'payment_method' => 'Efectivo',
            'payment_status' => 'Pagado',
            'paid_amount' => 135000,
            'pending_balance' => 0,
            'payment_notes' => 'Pago completo en efectivo en caja principal',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('total', 135000)
            ->assertJsonPath('payment_method', 'Efectivo')
            ->assertJsonPath('payment_status', 'Pagado')
            ->assertJsonPath('paid_amount', 135000)
            ->assertJsonPath('pending_balance', 0);

        $this->assertDatabaseHas('Sales', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'total' => 135000,
            'pending_balance' => 0,
        ]);
    }

    public function test_pos_registers_credit_sale_and_processes_subsequent_partial_and_final_abonos(): void
    {
        $customer = Customer::create([
            'name' => 'Transportes Especiales del Café S.A.S.',
            'phone' => '3154443322',
            'email' => 'contabilidad@transportescafe.co',
        ]);

        // 1. Registrar venta a crédito con abono inicial de $50.000 sobre un total de $200.000
        $createResponse = $this->postJson('/api/sales', [
            'customer_id' => $customer->id,
            'total' => 200000,
            'payment_method' => 'Crédito / Pendiente',
            'paid_amount' => 50000,
            'payment_notes' => 'Abono inicial en efectivo de $50.000',
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('payment_status', 'Parcial')
            ->assertJsonPath('paid_amount', 50000)
            ->assertJsonPath('pending_balance', 150000);

        $saleId = $createResponse->json('id');

        // 2. Registrar segundo abono parcial de $80.000 vía Nequi
        $firstAbonoResponse = $this->putJson("/api/sales/{$saleId}", [
            'abono' => 80000,
            'payment_notes' => 'Abono de $80.000 por transferencia Nequi',
        ]);

        $firstAbonoResponse->assertStatus(200)
            ->assertJsonPath('paid_amount', 130000)
            ->assertJsonPath('pending_balance', 70000)
            ->assertJsonPath('payment_status', 'Parcial');

        // 3. Saldar el saldo restante de $70.000
        $finalAbonoResponse = $this->putJson("/api/sales/{$saleId}", [
            'abono' => 70000,
            'payment_notes' => 'Cuenta saldada en su totalidad',
        ]);

        $finalAbonoResponse->assertStatus(200)
            ->assertJsonPath('paid_amount', 200000)
            ->assertJsonPath('pending_balance', 0)
            ->assertJsonPath('payment_status', 'Pagado');
    }

    public function test_sale_details_deduct_inventory_and_record_outgoing_kardex_movement(): void
    {
        $customer = Customer::create([
            'name' => 'Cliente Mostrador Repuestos',
            'phone' => '3001112233',
            'email' => 'mostrador@example.com',
        ]);

        $product = Product::create([
            'sku' => 'FRE-DIS-009',
            'name' => 'Disco de Freno Delantero Ventilado',
            'cost_price' => 75000,
            'price' => 115000,
            'min_stock' => 4,
        ]);

        Inventory::create([
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $sale = Sale::create([
            'customer_id' => $customer->id,
            'total' => 230000,
            'payment_method' => 'Transferencia / Nequi',
            'payment_status' => 'Pagado',
            'paid_amount' => 230000,
            'pending_balance' => 0,
        ]);

        $detailResponse = $this->postJson('/api/sale-details', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'subtotal' => 230000,
        ]);

        $detailResponse->assertStatus(201);

        $this->assertDatabaseHas('Inventory', [
            'product_id' => $product->id,
            'quantity' => 8,
        ]);

        $this->assertDatabaseHas('InventoryMovements', [
            'movement_type' => 'outgoing',
            'quantity' => 2,
        ]);
    }
}
