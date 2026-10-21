<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de solicitud para la actualización de datos de vehículos,
 * kilometraje actual, próximo cambio de aceite y vencimientos de SOAT y Tecnomecánica.
 */
class UpdateVehicleRequest extends FormRequest
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
            'customer_id' => ['sometimes', 'required', 'integer', 'exists:Customers,id'],
            'plate' => ['sometimes', 'required', 'string', 'max:20'],
            'make' => ['sometimes', 'required', 'string', 'max:100'],
            'model' => ['sometimes', 'required', 'string', 'max:100'],
            'current_mileage' => ['nullable', 'integer', 'min:0'],
            'next_oil_change_km' => ['nullable', 'integer', 'min:0'],
            'soat_due_date' => ['nullable', 'date'],
            'tecno_due_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.exists' => 'El propietario asignado no existe en la base de datos.',
            'plate.required' => 'La placa del vehículo no puede quedar vacía.',
            'make.required' => 'La marca del vehículo no puede quedar vacía.',
            'model.required' => 'El modelo o línea del vehículo es obligatorio.',
            'current_mileage.min' => 'El kilometraje actual debe ser un número positivo.',
            'next_oil_change_km.min' => 'El kilometraje del próximo cambio de aceite debe ser positivo.',
            'soat_due_date.date' => 'La fecha de vencimiento del SOAT no es una fecha válida.',
            'tecno_due_date.date' => 'La fecha de vencimiento de la Tecnomecánica no es una fecha válida.',
        ];
    }
}
