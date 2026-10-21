<?php

namespace App\Http\Requests;

use App\Services\WorkshopKanbanService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de solicitud para órdenes de trabajo y agendamiento Kanban de taller.
 */
class StoreScheduleRequest extends FormRequest
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
            'vehicle_id' => ['required', 'integer', 'exists:Vehicles,id'],
            'worker_id' => ['required', 'integer', 'exists:Workers,id'],
            'servicios' => ['required', 'array', 'min:1'],
            'servicios.*' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', Rule::in(['Pendiente', 'Completado', 'Cancelado'])],
            'workshop_status' => ['nullable', 'string', Rule::in(WorkshopKanbanService::KANBAN_STAGES)],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'El propietario o cliente de la orden de taller es obligatorio.',
            'vehicle_id.required' => 'El vehículo que ingresa al taller es obligatorio.',
            'worker_id.required' => 'Debes asignar un técnico o mecánico responsable a la orden.',
            'servicios.required' => 'Debes especificar al menos un servicio o trabajo por realizar.',
            'servicios.min' => 'La orden de trabajo debe contener al menos un servicio.',
            'workshop_status.in' => 'El estado Kanban indicado no pertenece a las etapas válidas del taller.',
            'mileage.min' => 'El kilometraje de recepción no puede ser un número negativo.',
        ];
    }
}
