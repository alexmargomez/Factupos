<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pruebas integrales para el porcentaje de comisión configurable por mecánico,
 * acumulación de mano de obra diaria y liquidación transaccional auditable.
 */
class WorkerCommissionsAndSettlementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create([
            'username' => 'admin_workers_test',
            'role' => 'Admin',
        ]));
    }

    public function test_worker_accumulates_daily_labor_and_settles_with_configured_commission_percentage(): void
    {
        // 1. Crear mecánico con 60% de comisión para el técnico y 40% para el taller
        $workerResponse = $this->postJson('/api/workers', [
            'name' => 'Jhon Jairo Aristizábal',
            'commission_percentage' => 60,
        ]);

        $workerResponse->assertStatus(201)
            ->assertJsonPath('commission_percentage', 60)
            ->assertJsonPath('totalDia', 0);

        $workerId = $workerResponse->json('id');

        $customer = Customer::create([
            'name' => 'Cliente Servicio Taller',
            'phone' => '3148887766',
        ]);

        $sale = Sale::create([
            'customer_id' => $customer->id,
            'worker_id' => $workerId,
            'total' => 150000,
            'payment_method' => 'Efectivo',
            'payment_status' => 'Pagado',
            'paid_amount' => 150000,
            'pending_balance' => 0,
        ]);

        // 2. Registrar dos servicios de mano de obra ($90.000 + $60.000 = $150.000)
        $this->postJson('/api/services', [
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'worker_id' => $workerId,
            'service' => 'Sincronización general de inyección electrónica',
            'price_service' => 90000,
        ])->assertStatus(201);

        $this->postJson('/api/services', [
            'sale_id' => $sale->id,
            'customer_id' => $customer->id,
            'worker_id' => $workerId,
            'service' => 'Calibración de válvulas y cambio de bujía',
            'price_service' => 60000,
        ])->assertStatus(201);

        $this->assertDatabaseHas('Workers', [
            'id' => $workerId,
            'totalDia' => 150000,
        ]);

        // 3. Liquidar al mecánico y verificar el cálculo 60% ($90.000) / 40% ($60.000)
        $settleResponse = $this->postJson("/api/workers/{$workerId}/settle", [
            'notes' => 'Liquidación turno sábado en efectivo',
        ]);

        $settleResponse->assertStatus(201)
            ->assertJsonPath('settlement.gross_amount', 150000)
            ->assertJsonPath('settlement.commission_percentage', 60)
            ->assertJsonPath('settlement.commission_amount', 90000)
            ->assertJsonPath('settlement.workshop_amount', 60000)
            ->assertJsonPath('worker.totalDia', 0);

        $this->assertDatabaseHas('WorkerSettlements', [
            'worker_id' => $workerId,
            'gross_amount' => 150000,
            'commission_amount' => 90000,
            'workshop_amount' => 60000,
        ]);
    }
}
