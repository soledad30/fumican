<?php

namespace App\Http\Requests\Servicios;

use App\Enums\EstadoConsultaEnum;
use App\Enums\PermisoEnum;
use App\Http\Requests\Servicios\Concerns\MapsConsultaMedicaPayload;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsultaMedicaRequest extends FormRequest
{
    use MapsConsultaMedicaPayload;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $canCreateMedicalConsultation = $this->user()->can(PermisoEnum::CREAR_CONSULTAS_MEDICAS->value);
        return $canCreateMedicalConsultation;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $esSeguimiento = $this->input('modo_consulta') === 'seguimiento';

        if ($esSeguimiento) {
            return [
                'modo_consulta' => 'nullable|in:inicial,seguimiento',
                'reason' => 'required|string|max:255',
                'general_condition' => 'required|string|max:120',
                'weight' => 'required|numeric|min:0',
                'presumptive_diagnosis' => 'nullable|string|max:120',
                'confirmatory_diagnosis' => 'nullable|string|max:120',
                'treatment' => 'nullable|string',
                'clinical_observation' => 'nullable|string',
                'consultation_fee' => 'nullable|numeric|min:0',
                'pet_id' => 'required|exists:mascotas,id',
                'veterinarian_id' => 'required|exists:usuarios,id',
                'service_id' => 'nullable|exists:servicios,id',
                'servicio_id' => 'nullable|exists:servicios,id',
                'estado' => ['nullable', Rule::in(EstadoConsultaEnum::values())],
                'fecha' => 'nullable|date',
                'hora' => 'nullable|string|max:10',
                'appetite' => 'nullable|string|max:120',
                'hydratation' => 'nullable|string|max:120',
                'mucosa' => 'nullable|string|max:120',
            ];
        }

        $estado = $this->input('estado');
        if (in_array($estado, [
            EstadoConsultaEnum::RESERVADA->value,
            EstadoConsultaEnum::EN_ESPERA->value,
        ], true)) {
            return [
                'reason' => 'required|string|max:255',
                'pet_id' => 'required|exists:mascotas,id',
                'fecha' => 'required|date',
                'hora' => 'required|string|max:10',
                'estado' => ['required', Rule::in(EstadoConsultaEnum::values())],
                'service_id' => 'nullable|exists:servicios,id',
                'servicio_id' => 'nullable|exists:servicios,id',
                'consultation_fee' => 'nullable|numeric|min:0',
                'veterinarian_id' => 'nullable|exists:usuarios,id',
                'modo_consulta' => 'nullable|in:inicial,seguimiento',
            ];
        }

        return [
            'reason' => 'required|string|max:255',
            'dewormed_at' => 'nullable|date',
            'previous_illnesses' => 'nullable|string|max:255',
            'previous_interventions' => 'nullable|string|max:255',
            'general_condition' => 'required|string|max:120',
            'weight' => 'required|numeric|min:0',
            'appetite' => 'required|string|max:120',
            'hydratation' => 'required|string|max:120',
            'mucosa' => 'required|string|max:120',
            'digestive_system' => 'nullable|string|max:120',
            'genitourinary_system' => 'nullable|string|max:120',
            'respiratory_system' => 'nullable|string|max:120',
            'temperature' => 'nullable|numeric',
            'heart_rate' => 'nullable|numeric',
            'respiratory_rate' => 'nullable|numeric',
            'clinical_observation' => 'nullable|string',
            'complementary_tests' => 'nullable|string|max:150',
            'pronostic' => 'nullable|string|max:150',
            'presumptive_diagnosis' => 'nullable|string|max:120',
            'confirmatory_diagnosis' => 'nullable|string|max:120',
            'treatment' => 'nullable|string',
            'consultation_fee' => 'nullable|numeric|min:0',
            'pet_id' => 'required|exists:mascotas,id',
            'veterinarian_id' => 'required|exists:usuarios,id',
            'service_id' => 'nullable|exists:servicios,id',
            'servicio_id' => 'nullable|exists:servicios,id',
            'estado' => ['nullable', Rule::in(EstadoConsultaEnum::values())],
            'fecha' => 'nullable|date',
            'hora' => 'nullable|string|max:10',
            'modo_consulta' => 'nullable|in:inicial,seguimiento',
        ];
    }

    public function validated($key = null, $default = null): array
    {
        parent::validated($key, $default);

        return $this->consultaMedicaPayload(withDefaults: true);
    }
}
