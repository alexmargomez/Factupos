<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define el estado por defecto del modelo Customer para pruebas automatizadas.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'phone' => '3' . $this->faker->numerify('#########'),
            'email' => $this->faker->unique()->safeEmail(),
        ];
    }
}
