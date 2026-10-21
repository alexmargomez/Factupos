<?php

namespace Database\Factories;

use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Worker>
 */
class WorkerFactory extends Factory
{
    protected $model = Worker::class;

    /**
     * Define el estado por defecto del modelo Worker con porcentaje de comisión configurable.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'totalDia' => 0,
            'commission_percentage' => $this->faker->randomElement([45, 50, 55, 60]),
        ];
    }
}
