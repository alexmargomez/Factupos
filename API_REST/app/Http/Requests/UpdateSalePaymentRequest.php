<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de solicitud para el registro de abonos sobre cuentas por cobrar
 * y actualización de estado de cartera en facturas POS.
 */
class UpdateSalePaymentRequest extends FormRequest
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
            'abono' => ['nullable', 'integer', 'min:1'],
            'paid_amount' => ['nullable', 'integer', 'min:0'],
            'pending_balance' => ['nullable', 'integer', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_status' => [
                'nullable',
                'string',
                Rule::in(['Pagado', 'Parcial', 'Pendiente']),
            ],
            'payment_notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'abono.integer' => 'El valor del abono a cartera debe ser un número entero en pesos ($ COP).',
            'abono.min' => 'El valor del abono debe ser de al menos $1 COP.',
            'payment_status.in' => 'El estado de cartera debe ser Pagado, Parcial o Pendiente.',
        ];
    }
}
