<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso JSON para la representación de facturas de venta POS,
 * estado de recaudo, abonos y saldo pendiente de cartera.
 */
class SaleResource extends JsonResource
{
    /**
     * Transforma el recurso en un arreglo serializable a JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $total = (int) ($this->total ?? 0);
        $paidAmount = (int) ($this->paid_amount ?? $total);
        $pendingBalance = (int) ($this->pending_balance ?? max(0, $total - $paidAmount));

        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice->invoice_number ?? sprintf('FACT-%06d', $this->id),
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'phone' => $this->customer->phone,
                'email' => $this->customer->email,
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
                'commission_percentage' => (int) ($this->worker->commission_percentage ?? 50),
            ]),
            'total' => $total,
            'payment_method' => $this->payment_method ?: 'Efectivo',
            'payment_status' => $this->payment_status ?: ($pendingBalance > 0 ? 'Parcial' : 'Pagado'),
            'paid_amount' => $paidAmount,
            'pending_balance' => $pendingBalance,
            'has_pending_balance' => $pendingBalance > 0,
            'payment_notes' => $this->payment_notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
