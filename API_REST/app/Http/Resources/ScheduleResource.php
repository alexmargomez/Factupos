<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso JSON para la representación de órdenes de taller y tarjetas del tablero Kanban.
 */
class ScheduleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $services = $this->servicios;
        if (is_string($services)) {
            $decoded = json_decode($services, true);
            $services = is_array($decoded) ? $decoded : [$services];
        }

        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
            ]),
            'vehicle_id' => $this->vehicle_id,
            'vehicle' => $this->whenLoaded('vehicle', fn () => [
                'id' => $this->vehicle->id,
                'plate' => $this->vehicle->plate,
                'make' => $this->vehicle->make,
                'model' => $this->vehicle->model,
            ]),
            'worker_id' => $this->worker_id,
            'worker' => $this->whenLoaded('worker', fn () => [
                'id' => $this->worker->id,
                'name' => $this->worker->name,
            ]),
            'state' => $this->state ?: 'Pendiente',
            'workshop_status' => $this->workshop_status ?: 'Recibido',
            'mileage' => $this->mileage !== null ? (int) $this->mileage : null,
            'notes' => $this->notes,
            'servicios' => is_array($services) ? array_values($services) : [],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
