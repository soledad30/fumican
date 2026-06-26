<?php

namespace App\Support;

use Carbon\Carbon;

final class LegacyFieldMapper
{
    public static function cliente(array $data): array
    {
        $genero = $data['gender'] ?? $data['genero'] ?? null;
        if ($genero !== null && $genero !== '') {
            $genero = match ((string) $genero) {
                '0', 'masculino', 'M', 'm' => 'masculino',
                '1', 'femenino', 'F', 'f' => 'femenino',
                default => 'otro',
            };
        }

        return array_filter([
            'nombre' => $data['first_name'] ?? $data['nombre'] ?? null,
            'apellido' => $data['last_name'] ?? $data['apellido'] ?? null,
            'ci' => $data['ci'] ?? null,
            'telefono' => $data['phone_number'] ?? $data['telefono'] ?? null,
            'email' => $data['email'] ?? null,
            'genero' => $genero,
            'fecha_nacimiento' => $data['birthdate'] ?? $data['fecha_nacimiento'] ?? null,
            'direccion' => $data['address'] ?? $data['direccion'] ?? null,
            'usuario_id' => $data['usuario_id'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public static function mascota(array $data): array
    {
        $fechaNacimiento = $data['fecha_nacimiento'] ?? $data['birth_date'] ?? null;

        if (empty($fechaNacimiento) && isset($data['age']) && is_numeric($data['age'])) {
            $edad = max(0, (int) $data['age']);
            $fechaNacimiento = Carbon::now()->subYears($edad)->format('Y-m-d');
        }

        return array_filter([
            'nombre' => $data['name'] ?? $data['nombre'] ?? null,
            'color' => $data['color'] ?? null,
            'cliente_id' => $data['customer_id'] ?? $data['cliente_id'] ?? null,
            'raza_id' => $data['breed_id'] ?? $data['raza_id'] ?? null,
            'url_foto' => $data['url_foto'] ?? $data['photo_url'] ?? null,
            'fecha_nacimiento' => $fechaNacimiento,
            'peso' => $data['weight'] ?? $data['peso'] ?? null,
            'genero' => $data['gender'] ?? $data['genero'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public static function veterinario(array $data): array
    {
        $esEspecialista = filter_var(
            $data['is_specialist'] ?? $data['es_especialista'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        return array_filter([
            'nombre' => $data['first_name'] ?? $data['nombre'] ?? null,
            'apellido' => $data['last_name'] ?? $data['apellido'] ?? null,
            'ci' => $data['ci'] ?? null,
            'telefono' => $data['phone_number'] ?? $data['telefono'] ?? null,
            'email' => $data['email'] ?? null,
            'licencia' => $data['license_number'] ?? $data['licencia'] ?? null,
            'es_especialista' => $esEspecialista,
            'especialidad' => $esEspecialista
                ? ($data['specialty'] ?? $data['especialidad'] ?? null)
                : null,
            'esta_activo' => array_key_exists('esta_activo', $data)
                ? (bool) $data['esta_activo']
                : ($data['is_active'] ?? true),
            'usuario_id' => $data['usuario_id'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
