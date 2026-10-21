<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso JSON para la representación de mecánicos/técnicos, su porcentaje de comisión
 * y el desglose en tiempo real entre pago al técnico y utilidad retenida por el taller.
 */
class WorkerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $grossDaily = max(0, (int) ($this->totalDia ?? 0));
        $commissionPct = max(0, min(100, (int) ($this->commission_percentage ?? 50)));
        $mechanicShare = (int) round(($grossDaily * $commissionPct) / 100);
        $workshopShare = $grossDaily - $mechanicShare;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'totalDia' => $grossDaily,
            'commission_percentage' => $commissionPct,
            'workshop_percentage' => 100 - $commissionPct,
            'calculated_mechanic_payout' => $mechanicShare,
            'calculated_workshop_share' => $workshopShare,
            'settlements' => $this->whenLoaded('settlements'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
