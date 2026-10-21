<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de solicitud para registro de gastos menores, salidas de caja
 * y pagos operativos incluidos en el arqueo diario.
 */
class StoreExpenseRequest extends FormRequest
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
            'description' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:80'],
            'amount' => ['required', 'integer', 'min:1'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'expense_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.required' => 'El concepto o descripción del gasto de caja es obligatorio.',
            'amount.required' => 'El monto del gasto es obligatorio.',
            'amount.min' => 'El valor del gasto debe ser mayor a $0 COP.',
        ];
    }
}
