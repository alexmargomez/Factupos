<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * Define el estado por defecto del modelo Supplier para pruebas de directorio de proveedores.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company() . ' Repuestos S.A.S.',
            'nit' => $this->faker->numerify('900.###.###-#'),
            'contact_name' => $this->faker->name(),
            'phone' => '3' . $this->faker->numerify('#########'),
            'email' => $this->faker->unique()->companyEmail(),
            'address' => $this->faker->streetAddress(),
            'notes' => 'Proveedor mayorista de autopartes y lubricantes.',
        ];
    }
}
