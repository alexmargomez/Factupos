<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Service;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pruebas integrales de arqueo de caja, registro de gastos menores/salidas operativas
 * y consolidación de utilidad neta real en el endpoint ejecutivo de analítica.
 */
class CashboxExpensesAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create([
            'username' => 'admin_cashbox_test',
            'role' => 'Admin',
        ]));
    }

    public function test_can_register_petty_cash_expenses_and_compute_net_operating_profit(): void
    {
        $customer = Customer::create([
            'name' => 'Cliente Arqueo General',
            'phone' => '3014445566',
        ]);

        $worker = Worker::create([
            'name' => 'Técnico Arqueo',
            'totalDia' => 0,
            'commission_percentage' => 50,
        ]);

        $product = Product::create([
            'sku' => 'FIL-AIRE-01',
            'name' => 'Filtro de Aire Original',
            'cost_price' => 20000,
            'price' => 35000,
            'min_stock' => 5,
        ]);

        // Venta de 2 filtros ($70.000 ingreso, $40.000 costo => $30.000 utilidad repuestos) + $40.000 mano de obra
        $sale = Sale::create([
            'customer_id' => $customer->id,
            'worker_id' => $worker->id,
            'total' => 110000,
            'payment_method' => 'Efectivo',
            'payment_status' => 'Pagado',
            'paid_amount' => 110000,
            'pending_balance' => 0,
        ]);

        SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'subtotal' => 70000,
        ]);

        Service::create([
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'worker_id' => $worker->id,
            'service' => 'Mantenimiento general de admisión',
            'price_service' => 40000,
        ]);

        // Registrar gasto menor de caja por $15.000
        $expenseResponse = $this->postJson('/api/expenses', [
            'description' => 'Compra de limpiador de cuerpos de aceleración',
            'category' => 'Insumos Taller',
            'amount' => 15000,
            'payment_method' => 'Efectivo',
        ]);

        $expenseResponse->assertStatus(201)
            ->assertJsonPath('amount', 15000);

        $financialResponse = $this->getJson('/api/analytics/financial');

        $financialResponse->assertStatus(200)
            ->assertJsonPath('kpis.total_invoiced', 110000)
            ->assertJsonPath('kpis.total_collected', 110000)
            ->assertJsonPath('kpis.parts_revenue', 70000)
            ->assertJsonPath('kpis.parts_cost', 40000)
            ->assertJsonPath('kpis.parts_gross_profit', 30000)
            ->assertJsonPath('kpis.labor_revenue', 40000)
            ->assertJsonPath('kpis.total_expenses', 15000)
            ->assertJsonPath('kpis.net_operating_cash', 95000)
            ->assertJsonPath('kpis.real_net_profit', 55000);
    }
}
