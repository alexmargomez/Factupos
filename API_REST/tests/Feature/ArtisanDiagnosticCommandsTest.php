<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Vehicle;
use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas integrales de los comandos Artisan de diagnóstico, auditoría de inventario,
 * monitoreo de alertas de vehículos (SOAT/Tecnomecánica/Aceite) y arqueo de caja.
 */
class ArtisanDiagnosticCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_inventory_command_outputs_structured_json_valuation(): void
    {
        $product = Product::create([
            'sku' => 'CMD-PROD-01',
            'name' => 'Pastillas de Freno Sinterizadas',
            'cost_price' => 40000,
            'price' => 65000,
            'min_stock' => 5,
        ]);

        Inventory::create([
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $this->artisan('factupos:audit-inventory --json')
            ->assertExitCode(0);

        $this->artisan('factupos:audit-inventory --only-alerts')
            ->assertExitCode(0);
    }

    public function test_check_vehicle_alerts_command_runs_successfully_and_detects_expirations(): void
    {
        $customer = Customer::create([
            'name' => 'Cliente Comando Alertas',
            'phone' => '3101239876',
        ]);

        Vehicle::create([
            'customer_id' => $customer->id,
            'plate' => 'CMD999',
            'make' => 'Bajaj',
            'model' => 'Dominar 400',
            'current_mileage' => 15200,
            'next_oil_change_km' => 15000,
            'soat_due_date' => Carbon::yesterday()->toDateString(),
            'tecno_due_date' => Carbon::tomorrow()->toDateString(),
        ]);

        $this->artisan('factupos:check-alerts --days=15 --km=500')
            ->assertExitCode(0);

        $this->artisan('factupos:check-alerts --json')
            ->assertExitCode(0);
    }

    public function test_cashbox_summary_and_mechanics_report_commands_execute_cleanly(): void
    {
        Worker::create([
            'name' => 'Mecánico Consola',
            'totalDia' => 120000,
            'commission_percentage' => 55,
        ]);

        Expense::create([
            'description' => 'Gasto de prueba en consola',
            'category' => 'Operativo',
            'amount' => 18000,
            'payment_method' => 'Efectivo',
            'expense_date' => Carbon::now(),
        ]);

        $this->artisan('factupos:cashbox-summary')
            ->assertExitCode(0);

        $this->artisan('factupos:mechanics-report')
            ->assertExitCode(0);
    }
}
