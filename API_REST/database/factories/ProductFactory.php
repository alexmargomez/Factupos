<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define el estado por defecto del modelo Product con SKU, costo y precio de venta.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = $this->faker->numberBetween(15, 180) * 1000;
        $marginMultiplier = $this->faker->randomFloat(2, 1.30, 1.65);
        $price = (int) (round(($cost * $marginMultiplier) / 1000) * 1000);

        return [
            'sku' => strtoupper($this->faker->bothify('REP-###??')),
            'name' => 'Repuesto ' . ucfirst($this->faker->words(3, true)),
            'cost_price' => $cost,
            'price' => max($cost + 5000, $price),
            'min_stock' => $this->faker->numberBetween(3, 10),
            'category_id' => null,
            'supplier_id' => null,
        ];
    }
}
