<?php

namespace Tests\Feature;

use App\Http\Requests\SettleWorkerRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\StoreInventoryMovementRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\StoreWorkerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UpdateSalePaymentRequest;
use App\Http\Requests\UpdateScheduleRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Requests\UpdateVehicleRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Pruebas integrales de las reglas de validación y mensajes personalizados en español
 * de todas las clases FormRequest del dominio de FactuPOS.
 */
class FormRequestValidationRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_product_request_validates_required_name_and_non_negative_prices(): void
    {
        $request = new StoreProductRequest();

        $invalidValidator = Validator::make([
            'name' => '',
            'cost_price' => -1500,
            'price' => -500,
            'min_stock' => -2,
        ], $request->rules(), $request->messages(), $request->attributes());

        $this->assertTrue($invalidValidator->fails());
        $this->assertArrayHasKey('name', $invalidValidator->errors()->toArray());
        $this->assertArrayHasKey('cost_price', $invalidValidator->errors()->toArray());
        $this->assertArrayHasKey('price', $invalidValidator->errors()->toArray());
        $this->assertArrayHasKey('min_stock', $invalidValidator->errors()->toArray());

        $validValidator = Validator::make([
            'sku' => 'REP-VAL-001',
            'name' => 'Kit de Cilindro Original 150cc',
            'cost_price' => 125000,
            'price' => 185000,
            'min_stock' => 4,
            'stock' => 10,
        ], $request->rules(), $request->messages(), $request->attributes());

        $this->assertFalse($validValidator->fails());
    }

    public function test_store_sale_and_update_payment_requests_validate_payment_methods_and_abonos(): void
    {
        $saleRequest = new StoreSaleRequest();

        $invalidMethodValidator = Validator::make([
            'customer_id' => 999999,
            'total' => -100,
            'payment_method' => 'Criptomoneda No Soportada',
        ], $saleRequest->rules(), $saleRequest->messages());

        $this->assertTrue($invalidMethodValidator->fails());
        $this->assertArrayHasKey('payment_method', $invalidMethodValidator->errors()->toArray());
        $this->assertArrayHasKey('total', $invalidMethodValidator->errors()->toArray());

        $abonoRequest = new UpdateSalePaymentRequest();
        $invalidAbonoValidator = Validator::make([
            'abono' => 0,
            'payment_status' => 'EstadoInvalido',
        ], $abonoRequest->rules(), $abonoRequest->messages());

        $this->assertTrue($invalidAbonoValidator->fails());
        $this->assertArrayHasKey('abono', $invalidAbonoValidator->errors()->toArray());
        $this->assertArrayHasKey('payment_status', $invalidAbonoValidator->errors()->toArray());
    }

    public function test_store_schedule_and_vehicle_requests_validate_kanban_stage_and_mileage(): void
    {
        $scheduleRequest = new StoreScheduleRequest();

        $invalidKanbanValidator = Validator::make([
            'workshop_status' => 'Etapa Inexistente',
            'mileage' => -50,
            'servicios' => [],
        ], $scheduleRequest->rules(), $scheduleRequest->messages());

        $this->assertTrue($invalidKanbanValidator->fails());
        $this->assertArrayHasKey('workshop_status', $invalidKanbanValidator->errors()->toArray());
        $this->assertArrayHasKey('mileage', $invalidKanbanValidator->errors()->toArray());
        $this->assertArrayHasKey('servicios', $invalidKanbanValidator->errors()->toArray());

        $vehicleRequest = new StoreVehicleRequest();
        $invalidVehicleValidator = Validator::make([
            'plate' => '',
            'make' => '',
            'model' => '',
            'current_mileage' => -100,
            'soat_due_date' => 'fecha-invalida',
        ], $vehicleRequest->rules(), $vehicleRequest->messages());

        $this->assertTrue($invalidVehicleValidator->fails());
        $this->assertArrayHasKey('plate', $invalidVehicleValidator->errors()->toArray());
        $this->assertArrayHasKey('soat_due_date', $invalidVehicleValidator->errors()->toArray());
    }

    public function test_worker_expense_supplier_customer_and_movement_requests_enforce_constraints(): void
    {
        $workerRequest = new StoreWorkerRequest();
        $invalidWorker = Validator::make([
            'name' => '',
            'commission_percentage' => 145,
        ], $workerRequest->rules(), $workerRequest->messages());
        $this->assertTrue($invalidWorker->fails());

        $settleRequest = new SettleWorkerRequest();
        $validSettle = Validator::make([
            'notes' => 'Liquidación quincenal autorizada',
            'commission_percentage' => 55,
        ], $settleRequest->rules(), $settleRequest->messages());
        $this->assertFalse($validSettle->fails());

        $expenseRequest = new StoreExpenseRequest();
        $invalidExpense = Validator::make([
            'description' => '',
            'amount' => 0,
        ], $expenseRequest->rules(), $expenseRequest->messages());
        $this->assertTrue($invalidExpense->fails());

        $supplierRequest = new StoreSupplierRequest();
        $invalidSupplier = Validator::make([
            'name' => '',
            'email' => 'correo-sin-arroba',
        ], $supplierRequest->rules(), $supplierRequest->messages());
        $this->assertTrue($invalidSupplier->fails());

        $customerRequest = new StoreCustomerRequest();
        $invalidCustomer = Validator::make([
            'name' => '',
            'email' => 'formato_invalido',
        ], $customerRequest->rules(), $customerRequest->messages());
        $this->assertTrue($invalidCustomer->fails());

        $movementRequest = new StoreInventoryMovementRequest();
        $invalidMovement = Validator::make([
            'movement_type' => 'transferencia_interna',
            'quantity' => 0,
        ], $movementRequest->rules(), $movementRequest->messages());
        $this->assertTrue($invalidMovement->fails());

        // Verificar autorización y reglas de los FormRequests de actualización
        $this->assertTrue((new UpdateProductRequest())->authorize());
        $this->assertTrue((new UpdateVehicleRequest())->authorize());
        $this->assertTrue((new UpdateScheduleRequest())->authorize());
        $this->assertTrue((new UpdateSupplierRequest())->authorize());
        $this->assertTrue((new UpdateCustomerRequest())->authorize());
    }
}
