<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso JSON para la representación de vehículos con indicadores
 * de mantenimiento preventivo (kilometraje de aceite, SOAT y Tecnomecánica).
 */
class VehicleResource extends JsonResource
{
    /**
     * Transforma el recurso en un arreglo serializable a JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $today = Carbon::today()->toDateString();
        $oilOverdue = $this->current_mileage !== null
            && $this->next_oil_change_km !== null
            && (int) $this->current_mileage >= (int) $this->next_oil_change_km;
        $soatExpired = !empty($this->soat_due_date) && $this->soat_due_date < $today;
        $tecnoExpired = !empty($this->tecno_due_date) && $this->tecno_due_date < $today;

        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
            ]),
            'plate' => $this->plate,
            'make' => $this->make,
            'model' => $this->model,
            'current_mileage' => $this->current_mileage !== null ? (int) $this->current_mileage : null,
            'next_oil_change_km' => $this->next_oil_change_km !== null ? (int) $this->next_oil_change_km : null,
            'soat_due_date' => $this->soat_due_date,
            'tecno_due_date' => $this->tecno_due_date,
            'maintenance_alerts' => [
                'oil_change_overdue' => $oilOverdue,
                'soat_expired' => $soatExpired,
                'tecno_expired' => $tecnoExpired,
                'requires_attention' => $oilOverdue || $soatExpired || $tecnoExpired,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
