<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de solicitud para registro de técnicos/mecánicos y su esquema de comisión.
 */
class StoreWorkerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'commission_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'totalDia' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre completo del mecánico o técnico es obligatorio.',
            'commission_percentage.integer' => 'El porcentaje de comisión debe ser un número entero entre 0 y 100.',
            'commission_percentage.min' => 'El porcentaje de comisión no puede ser menor a 0%.',
            'commission_percentage.max' => 'El porcentaje de comisión no puede superar el 100%.',
        ];
    }
}
