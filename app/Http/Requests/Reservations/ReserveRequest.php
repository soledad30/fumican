<?php

namespace App\Http\Requests\Reservations;

use Illuminate\Foundation\Http\FormRequest;

class ReserveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'petName' => 'required|string|max:255',
            'service' => 'required|string|max:255',
            'date' => 'required|date',
            'timeSlot' => 'required|string|max:50',
            'comment' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'phone.required' => 'El teléfono es obligatorio.',      
            'email.required' => 'El correo electrónico es obligatorio.',
            'petName.required' => 'El nombre de la mascota es obligatorio.',
            'service.required' => 'El servicio es obligatorio.',
            'date.required' => 'La fecha es obligatoria.',
            'timeSlot.required' => 'El horario es obligatorio.',
            'comment.max' => 'El comentario no puede exceder los 500 caracteres.',
        ];
    }

    
}
