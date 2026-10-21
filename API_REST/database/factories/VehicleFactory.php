<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * Define el estado por defecto del modelo Vehicle con kilometraje y fechas SOAT/Tecno.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currentKm = $this->faker->numberBetween(5000, 85000);
        $makes = ['Yamaha', 'Honda', 'Suzuki', 'Bajaj', 'AKT', 'Chevrolet', 'Renault', 'Mazda'];
        $models = ['FZ 2.0', 'CB 190R', 'Gixxer 150', 'Pulsar NS200', 'NKD 125', 'Onix Turbo', 'Duster', 'CX-30'];

        return [
            'customer_id' => Customer::factory(),
            'plate' => strtoupper($this->faker->bothify('???###')),
            'make' => $this->faker->randomElement($makes),
            'model' => $this->faker->randomElement($models),
            'current_mileage' => $currentKm,
            'next_oil_change_km' => $currentKm + 3000,
            'soat_due_date' => Carbon::today()->addMonths($this->faker->numberBetween(1, 10))->toDateString(),
            'tecno_due_date' => Carbon::today()->addMonths($this->faker->numberBetween(1, 10))->toDateString(),
        ];
    }
}
