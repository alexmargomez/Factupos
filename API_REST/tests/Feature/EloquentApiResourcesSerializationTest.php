<?php

namespace Tests\Feature;

use App\Http\Resources\CustomerResource;
use App\Http\Resources\ExpenseResource;
use App\Http\Resources\InventoryMovementResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\SaleResource;
use App\Http\Resources\ScheduleResource;
use App\Http\Resources\SupplierResource;
use App\Http\Resources\VehicleResource;
use App\Http\Resources\WorkerResource;
use App\Http\Resources\WorkerSettlementResource;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Schedule;
use App\Models\Supplier;
use App\Models\Vehicle;
use App\Models\Worker;
use App\Models\WorkerSettlement;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Pruebas integrales de serialización de todas las clases Eloquent JsonResource
 * para garantizar contratos JSON consistentes y cálculos derivados exactos.
 */
class EloquentApiResourcesSerializationTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_and_supplier_resources_compute_margin_and_valuation_fields(): void
    {
        $request = Request::create('/api/products', 'GET');

        $supplier = Supplier::create([
            'name' => 'Importadora Japonesa S.A.S.',
            'nit' => '900.111.222-3',
            'phone' => '3104445566',
        ]);

        $product = Product::create([
            'sku' => 'JAP-001',
            'name' => 'Cadena de Distribución DID',
            'cost_price' => 60000,
            'price' => 100000,
            'min_stock' => 5,
            'supplier_id' => $supplier->id,
        ]);

        $inventory = Inventory::create([
            'product_id' => $product->id,
            'quantity' => 4,
        ]);

        $movement = InventoryMovement::create([
            'inventory_id' => $inventory->id,
            'movement_type' => 'incoming',
            'quantity' => 4,
            'reason' => 'Ingreso inicial',
            'movement_date' => Carbon::now(),
        ]);

        $product->load(['supplier', 'inventory']);
        $productArray = (new ProductResource($product))->toArray($request);

        $this->assertSame('JAP-001', $productArray['sku']);
        $this->assertSame(40000, $productArray['unit_profit']);
        $this->assertSame(40.0, $productArray['margin_percentage']);
        $this->assertTrue($productArray['is_low_stock']);
        $this->assertFalse($productArray['is_out_of_stock']);
        $this->assertSame(240000, $productArray['total_cost_valuation']);
        $this->assertSame(400000, $productArray['total_sale_valuation']);

        $supplierArray = (new SupplierResource($supplier))->toArray($request);
        $this->assertSame('Importadora Japonesa S.A.S.', $supplierArray['name']);

        $movement->load('inventory.product');
        $movementArray = (new InventoryMovementResource($movement))->toArray($request);
        $this->assertSame('Entrada', $movementArray['movement_type_label']);
        $this->assertSame('+4', $movementArray['signed_quantity']);
    }

    public function test_vehicle_sale_schedule_worker_and_expense_resources_format_domain_attributes(): void
    {
        $request = Request::create('/api/analytics/summary', 'GET');

        $customer = Customer::create([
            'name' => 'Cliente Recursos JSON',
            'phone' => '3150001122',
            'email' => 'recursos@example.com',
        ]);

        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate' => 'RES001',
            'make' => 'Honda',
            'model' => 'CB 300F Twister',
            'current_mileage' => 16000,
            'next_oil_change_km' => 15500,
            'soat_due_date' => Carbon::yesterday()->toDateString(),
            'tecno_due_date' => Carbon::tomorrow()->toDateString(),
        ]);

        $worker = Worker::create([
            'name' => 'Mecánico Recursos',
            'totalDia' => 100000,
            'commission_percentage' => 55,
        ]);

        $settlement = WorkerSettlement::create([
            'worker_id' => $worker->id,
            'gross_amount' => 100000,
            'commission_percentage' => 55,
            'commission_amount' => 55000,
            'workshop_amount' => 45000,
            'settled_at' => Carbon::now(),
            'notes' => 'Cierre de prueba',
        ]);

        $schedule = Schedule::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'worker_id' => $worker->id,
            'state' => 'Pendiente',
            'workshop_status' => 'En Diagnóstico',
            'mileage' => 16000,
            'servicios' => json_encode(['Diagnóstico electrónico']),
        ]);

        $sale = Sale::create([
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'worker_id' => $worker->id,
            'total' => 140000,
            'payment_method' => 'Crédito / Pendiente',
            'payment_status' => 'Parcial',
            'paid_amount' => 90000,
            'pending_balance' => 50000,
        ]);

        $expense = Expense::create([
            'description' => 'Compra de insumos de limpieza',
            'category' => 'Operativo',
            'amount' => 22000,
            'payment_method' => 'Efectivo',
            'expense_date' => Carbon::now(),
        ]);

        $vehicleArray = (new VehicleResource($vehicle))->toArray($request);
        $this->assertTrue($vehicleArray['maintenance_alerts']['oil_change_overdue']);
        $this->assertTrue($vehicleArray['maintenance_alerts']['soat_expired']);
        $this->assertFalse($vehicleArray['maintenance_alerts']['tecno_expired']);
        $this->assertTrue($vehicleArray['maintenance_alerts']['requires_attention']);

        $workerArray = (new WorkerResource($worker))->toArray($request);
        $this->assertSame(55000, $workerArray['calculated_mechanic_payout']);
        $this->assertSame(45000, $workerArray['calculated_workshop_share']);

        $settlementArray = (new WorkerSettlementResource($settlement))->toArray($request);
        $this->assertSame(45, $settlementArray['workshop_percentage']);

        $scheduleArray = (new ScheduleResource($schedule))->toArray($request);
        $this->assertSame('En Diagnóstico', $scheduleArray['workshop_status']);
        $this->assertSame(['Diagnóstico electrónico'], $scheduleArray['servicios']);

        $saleArray = (new SaleResource($sale))->toArray($request);
        $this->assertTrue($saleArray['has_pending_balance']);
        $this->assertSame(50000, $saleArray['pending_balance']);

        $expenseArray = (new ExpenseResource($expense))->toArray($request);
        $this->assertSame(22000, $expenseArray['amount']);

        $customerArray = (new CustomerResource($customer))->toArray($request);
        $this->assertSame('Cliente Recursos JSON', $customerArray['name']);
    }
}
