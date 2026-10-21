<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de solicitud para registro y actualización de vehículos,
 * kilometraje preventivo y fechas de vencimiento de SOAT y Tecnomecánica.
 */
class StoreVehicleRequest extends FormRequest
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
            'plate' => ['required', 'string', 'max:20'],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
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
            'customer_id.required' => 'El vehículo debe estar asociado a un cliente registrado.',
            'plate.required' => 'La placa del vehículo es obligatoria.',
            'make.required' => 'La marca del vehículo es obligatoria.',
            'model.required' => 'La línea o modelo del vehículo es obligatoria.',
            'current_mileage.min' => 'El kilometraje actual no puede ser menor a cero.',
            'next_oil_change_km.min' => 'El kilometraje del próximo cambio de aceite no puede ser negativo.',
            'soat_due_date.date' => 'La fecha de vencimiento del SOAT no tiene un formato válido.',
            'tecno_due_date.date' => 'La fecha de vencimiento de la Tecnomecánica no tiene un formato válido.',
        ];
    }
}
