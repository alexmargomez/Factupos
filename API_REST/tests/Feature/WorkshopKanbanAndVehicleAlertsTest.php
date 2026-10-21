<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pruebas integrales para el tablero Kanban de órdenes de taller,
 * sincronización automática de kilometraje del vehículo y detección de alertas
 * preventivas de cambio de aceite, SOAT y Tecnomecánica.
 */
class WorkshopKanbanAndVehicleAlertsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create([
            'username' => 'admin_workshop_test',
            'role' => 'Admin',
        ]));
    }

    public function test_creating_workshop_order_updates_vehicle_mileage_and_supports_kanban_transitions(): void
    {
        $customer = Customer::create([
            'name' => 'María Fernanda Londoño',
            'phone' => '3112223344',
            'email' => 'mflondono@example.com',
        ]);

        $vehicle = Vehicle::create([
            'customer_id' => $customer->id,
            'plate' => 'ZYX987',
            'make' => 'Honda',
            'model' => 'PCX 160',
            'current_mileage' => 10000,
            'next_oil_change_km' => 13000,
        ]);

        $worker = Worker::create([
            'name' => 'Técnico Especialista Kanban',
            'totalDia' => 0,
            'commission_percentage' => 50,
        ]);

        // 1. Crear orden de trabajo con nuevo kilometraje de ingreso (12.850 km)
        $scheduleResponse = $this->postJson('/api/schedules', [
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'worker_id' => $worker->id,
            'state' => 'Pendiente',
            'workshop_status' => 'Recibido',
            'mileage' => 12850,
            'notes' => 'Deja casco integral negro y impermeable en baúl',
            'servicios' => ['Cambio de aceite y filtro', 'Mantenimiento de mordaza delantera'],
        ]);

        $scheduleResponse->assertStatus(201)
            ->assertJsonPath('workshop_status', 'Recibido')
            ->assertJsonPath('mileage', 12850);

        // El vehículo debe haber actualizado automáticamente su kilometraje actual
        $this->assertDatabaseHas('Vehicles', [
            'id' => $vehicle->id,
            'current_mileage' => 12850,
        ]);

        $scheduleId = $scheduleResponse->json('id');

        // 2. Avanzar tarjeta en el tablero Kanban a "En Reparación" y luego a "Listo para Entrega"
        $this->putJson("/api/schedules/{$scheduleId}", [
            'workshop_status' => 'En Reparación',
        ])->assertStatus(200)
            ->assertJsonPath('workshop_status', 'En Reparación');

        $this->putJson("/api/schedules/{$scheduleId}", [
            'workshop_status' => 'Listo para Entrega',
        ])->assertStatus(200)
            ->assertJsonPath('workshop_status', 'Listo para Entrega');
    }

    public function test_vehicle_alerts_endpoint_detects_overdue_oil_and_expired_soat_and_tecno(): void
    {
        $customer = Customer::create([
            'name' => 'Propietario Alerta Preventiva',
            'phone' => '3209991122',
        ]);

        Vehicle::create([
            'customer_id' => $customer->id,
            'plate' => 'ALT123',
            'make' => 'Suzuki',
            'model' => 'V-Strom 250',
            'current_mileage' => 26500,
            'next_oil_change_km' => 25000,
            'soat_due_date' => Carbon::yesterday()->toDateString(),
            'tecno_due_date' => Carbon::yesterday()->toDateString(),
        ]);

        $response = $this->getJson('/api/analytics/vehicle-alerts');

        $response->assertStatus(200)
            ->assertJsonPath('vehicles_with_alerts_count', 1)
            ->assertJsonPath('counts.oil_overdue', 1)
            ->assertJsonPath('counts.soat_expired', 1)
            ->assertJsonPath('counts.tecno_expired', 1);
    }
}
