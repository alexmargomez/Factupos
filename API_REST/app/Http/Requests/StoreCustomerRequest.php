<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de solicitud para el alta y actualización de clientes en FactuPOS.
 */
class StoreCustomerRequest extends FormRequest
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
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre completo o razón social del cliente es obligatorio.',
            'name.max' => 'El nombre del cliente no puede exceder los 255 caracteres.',
            'email.email' => 'El correo electrónico ingresado no tiene un formato válido.',
        ];
    }
}
