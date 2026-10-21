<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso JSON para la representación de gastos menores y salidas de caja.
 */
class ExpenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'category' => $this->category ?: 'Operativo',
            'amount' => (int) $this->amount,
            'payment_method' => $this->payment_method ?: 'Efectivo',
            'expense_date' => $this->expense_date?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
