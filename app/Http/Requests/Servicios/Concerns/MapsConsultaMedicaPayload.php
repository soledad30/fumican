<?php

namespace App\Http\Requests\Servicios\Concerns;

trait MapsConsultaMedicaPayload
{
    protected function consultaMedicaPayload(bool $withDefaults = false): array
    {
        $diagnostico = $this->input('confirmatory_diagnosis')
            ?: $this->input('presumptive_diagnosis');

        $payload = array_filter([
            'motivo' => $this->input('reason'),
            'mascota_id' => $this->input('pet_id'),
            'usuario_id' => $this->input('veterinarian_id'),
            'costo_consulta' => $this->input('consultation_fee'),
            'diagnostico' => $diagnostico,
            'fecha' => $this->input('fecha'),
            'hora' => $this->input('hora'),
            'servicio_id' => $this->input('service_id') ?? $this->input('servicio_id'),
            'estado' => $this->input('estado'),
        ], fn ($value) => $value !== null && $value !== '');

        if ($withDefaults) {
            $payload['fecha'] ??= now()->toDateString();
            $payload['hora'] ??= now()->format('H:i:s');
            if (empty($payload['estado'])) {
                $payload['estado'] = 'completada';
            }
        }

        return $payload;
    }
}
