<?php

namespace Database\Factories;

use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    /**
     * Define el estado por defecto del modelo Expense para pruebas de arqueo de caja.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => $this->faker->randomElement([
                'Compra de desengrasante y estopa para taller',
                'Pago de servicio de mensajería de repuestos',
                'Insumos de cafetería y atención a clientes',
                'Transporte urgente de torno y prensa hidráulica',
            ]),
            'category' => $this->faker->randomElement(['Operativo', 'Insumos Taller', 'Servicios', 'Otros']),
            'amount' => $this->faker->numberBetween(8, 85) * 1000,
            'payment_method' => $this->faker->randomElement(['Efectivo', 'Transferencia / Nequi']),
            'expense_date' => Carbon::now(),
        ];
    }
}
