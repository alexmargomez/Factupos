<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de solicitud para registro y actualización de ventas POS,
 * pagos mixtos, créditos a clientes y abonos de cartera.
 */
class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:Customers,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:Vehicles,id'],
            'worker_id' => ['nullable', 'integer', 'exists:Workers,id'],
            'total' => ['required', 'integer', 'min:0'],
            'payment_method' => [
                'nullable',
                'string',
                'max:50',
                Rule::in([
                    'Efectivo',
                    'Transferencia / Nequi',
                    'Tarjeta',
                    'Pago Mixto',
                    'Crédito / Pendiente',
                ]),
            ],
            'payment_status' => [
                'nullable',
                'string',
                Rule::in(['Pagado', 'Parcial', 'Pendiente']),
            ],
            'paid_amount' => ['nullable', 'integer', 'min:0'],
            'pending_balance' => ['nullable', 'integer', 'min:0'],
            'payment_notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Debes seleccionar un cliente válido para registrar la factura.',
            'customer_id.exists' => 'El cliente seleccionado no existe en la base de datos.',
            'vehicle_id.exists' => 'El vehículo asociado no existe en el registro.',
            'worker_id.exists' => 'El mecánico asignado no se encuentra registrado.',
            'total.required' => 'El valor total de la venta es obligatorio.',
            'total.min' => 'El total de la venta no puede ser negativo.',
            'payment_method.in' => 'El método de pago seleccionado no es válido.',
            'paid_amount.min' => 'El monto pagado o abonado no puede ser negativo.',
        ];
    }
}
