<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Vehicle;
use App\Models\Worker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    /**
     * Define el estado por defecto del modelo Sale con soporte de métodos de pago y cartera.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = $this->faker->numberBetween(45, 450) * 1000;

        return [
            'customer_id' => Customer::factory(),
            'vehicle_id' => Vehicle::factory(),
            'worker_id' => Worker::factory(),
            'total' => $total,
            'payment_method' => $this->faker->randomElement([
                'Efectivo',
                'Transferencia / Nequi',
                'Tarjeta',
                'Pago Mixto',
            ]),
            'payment_status' => 'Pagado',
            'paid_amount' => $total,
            'pending_balance' => 0,
            'payment_notes' => 'Venta generada mediante factory de pruebas',
        ];
    }

    /**
     * Estado para simular una venta a crédito con saldo pendiente por cobrar.
     */
    public function onCredit(int $abono = 0): static
    {
        return $this->state(function (array $attributes) use ($abono) {
            $total = (int) ($attributes['total'] ?? 150000);
            $paid = max(0, min($total, $abono));
            $pending = max(0, $total - $paid);

            return [
                'payment_method' => 'Crédito / Pendiente',
                'payment_status' => $pending === 0 ? 'Pagado' : ($paid > 0 ? 'Parcial' : 'Pendiente'),
                'paid_amount' => $paid,
                'pending_balance' => $pending,
            ];
        });
    }
}
