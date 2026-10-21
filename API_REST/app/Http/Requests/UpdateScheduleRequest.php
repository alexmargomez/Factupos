<?php

namespace App\Http\Requests;

use App\Services\WorkshopKanbanService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación de solicitud para la actualización de estado Kanban, kilometraje
 * y observaciones técnicas en órdenes de taller.
 */
class UpdateScheduleRequest extends FormRequest
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
            'state' => ['nullable', 'string', Rule::in(['Pendiente', 'Completado', 'Cancelado'])],
            'workshop_status' => ['nullable', 'string', Rule::in(WorkshopKanbanService::KANBAN_STAGES)],
            'worker_id' => ['nullable', 'integer', 'exists:Workers,id'],
            'mileage' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'servicios' => ['nullable', 'array'],
            'servicios.*' => ['string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'state.in' => 'El estado general de la orden debe ser Pendiente, Completado o Cancelado.',
            'workshop_status.in' => 'La etapa Kanban seleccionada no es válida.',
            'worker_id.exists' => 'El técnico responsable seleccionado no existe.',
            'mileage.min' => 'El kilometraje no puede ser negativo.',
        ];
    }
}
