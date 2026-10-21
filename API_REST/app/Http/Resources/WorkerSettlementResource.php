<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso JSON para el historial auditable de liquidaciones pagadas a mecánicos.
 */
class WorkerSettlementResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'worker_id' => $this->worker_id,
            'worker_name' => $this->whenLoaded('worker', fn () => $this->worker->name),
            'gross_amount' => (int) $this->gross_amount,
            'commission_percentage' => (int) $this->commission_percentage,
            'workshop_percentage' => 100 - (int) $this->commission_percentage,
            'commission_amount' => (int) $this->commission_amount,
            'workshop_amount' => (int) $this->workshop_amount,
            'settled_at' => $this->settled_at?->toIso8601String(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
