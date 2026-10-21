<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de solicitud para la liquidación de comisiones de mecánicos
 * y cierre auditable de turno en el taller.
 */
class SettleWorkerRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:255'],
            'commission_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'notes.max' => 'La nota de liquidación no puede superar los 255 caracteres.',
            'commission_percentage.min' => 'El porcentaje de comisión no puede ser inferior a 0%.',
            'commission_percentage.max' => 'El porcentaje de comisión no puede exceder el 100%.',
        ];
    }
}
