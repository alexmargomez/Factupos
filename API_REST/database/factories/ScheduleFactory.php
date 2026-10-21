<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Schedule;
use App\Models\Vehicle;
use App\Models\Worker;
use App\Services\WorkshopKanbanService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    /**
     * Define el estado por defecto del modelo Schedule para órdenes Kanban de taller.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'vehicle_id' => Vehicle::factory(),
            'worker_id' => Worker::factory(),
            'state' => 'Pendiente',
            'workshop_status' => $this->faker->randomElement(WorkshopKanbanService::KANBAN_STAGES),
            'mileage' => $this->faker->numberBetween(5000, 75000),
            'notes' => 'Vehículo recibido con nivel de combustible a medio tanque y documentos en carpeta.',
            'servicios' => json_encode([
                'Cambio de aceite y filtro',
                'Revisión general de frenos delanteros y traseros',
                'Lubricación y tensión de kit de arrastre',
            ], JSON_UNESCAPED_UNICODE),
        ];
    }
}
