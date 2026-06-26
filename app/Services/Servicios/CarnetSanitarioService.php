<?php

namespace App\Services\Servicios;

use App\Enums\EstadoConsultaEnum;
use App\Models\Servicios\Cliente;
use App\Models\Servicios\Mascota;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CarnetSanitarioService
{
    public function __construct(protected HistorialClinicoService $historialService) {}

    public function resumenPortalCliente(Cliente $cliente): array
    {
        $cliente->loadMissing(['mascotas.raza.especie']);

        return [
            'cliente' => [
                'id' => $cliente->id,
                'full_name' => $cliente->full_name,
                'tiene_usuario' => (bool) $cliente->usuario_id,
            ],
            'mascotas' => $cliente->mascotas->map(fn (Mascota $m) => $this->tarjetaMascota($m))->values()->all(),
        ];
    }

    public function carnetMascota(Mascota $mascota): array
    {
        $historial = $this->historialService->obtenerHistorial($mascota);
        $vacunas = $this->estadoVacunas($mascota);
        $citas = $this->proximasCitas($mascota);

        return [
            'mascota' => $historial['mascota'],
            'resumen' => $historial['resumen'],
            'vacunas' => $vacunas,
            'proximas_citas' => $citas,
            'eventos_recientes' => $historial['eventos']->take(10)->values()->all(),
            'alertas' => $this->alertas($vacunas, $citas),
        ];
    }

    public function resumenClinicoConsulta(Mascota $mascota): array
    {
        $mascota->loadMissing(['raza.especie', 'propietario']);

        $totalCompletadas = $mascota->consultas()
            ->where('estado', EstadoConsultaEnum::COMPLETADA->value)
            ->count();

        $ultima = $mascota->consultas()
            ->where('estado', EstadoConsultaEnum::COMPLETADA->value)
            ->orderByDesc('fecha')
            ->first();

        $vacunas = $this->estadoVacunas($mascota);

        return [
            'es_paciente_nuevo' => $totalCompletadas === 0,
            'total_consultas_completadas' => $totalCompletadas,
            'peso_actual' => $mascota->peso ?? $mascota->weight,
            'ultima_consulta' => $ultima ? [
                'fecha' => $ultima->fecha,
                'motivo' => $ultima->motivo,
                'diagnostico' => $ultima->diagnostico,
            ] : null,
            'vacunas_pendientes' => collect($vacunas)->whereIn('estado', ['vencido', 'proximo', 'pendiente'])->count(),
            'proximas_citas' => $this->proximasCitas($mascota),
        ];
    }

    private function tarjetaMascota(Mascota $mascota): array
    {
        $vacunas = $this->estadoVacunas($mascota);
        $alertas = collect($vacunas)->whereIn('estado', ['vencido', 'proximo'])->count();

        return [
            'id' => $mascota->id,
            'nombre' => $mascota->nombre ?? $mascota->name,
            'especie' => $mascota->raza?->especie?->nombre,
            'raza' => $mascota->raza?->nombre,
            'peso' => $mascota->peso ?? $mascota->weight,
            'photo_url' => $mascota->photo_url,
            'alertas_sanitarias' => $alertas,
            'tiene_citas' => count($this->proximasCitas($mascota)) > 0,
            'proximas_citas' => $this->proximasCitas($mascota),
        ];
    }

    private function estadoVacunas(Mascota $mascota): array
    {
        $registros = $mascota->historialVacunacion()
            ->with('vacuna')
            ->orderByDesc('fecha_aplicacion')
            ->get();

        $porVacuna = $registros->groupBy('vacuna_id');

        return $porVacuna->map(function (Collection $items) {
            $ultimo = $items->first();

            return [
                'vacuna' => $ultimo->vacuna?->nombre ?? 'Vacuna',
                'ultima_aplicacion' => $ultimo->fecha_aplicacion?->format('d/m/Y'),
                'proxima' => $ultimo->fecha_proxima?->format('d/m/Y'),
                'estado' => $this->estadoFecha($ultimo->fecha_proxima),
                'estado_label' => $this->etiquetaEstado($this->estadoFecha($ultimo->fecha_proxima)),
            ];
        })->values()->all();
    }

    private function proximasCitas(Mascota $mascota): array
    {
        return $mascota->consultas()
            ->where('estado', EstadoConsultaEnum::RESERVADA->value)
            ->whereDate('fecha', '>=', now()->toDateString())
            ->orderBy('fecha')
            ->limit(5)
            ->get(['id', 'fecha', 'hora', 'motivo', 'servicio_id'])
            ->load('servicio')
            ->map(fn ($c) => [
                'id' => $c->id,
                'fecha' => $c->fecha,
                'hora' => $c->hora,
                'motivo' => $c->motivo,
                'servicio' => $c->servicio?->nombre,
            ])
            ->all();
    }

    private function alertas(array $vacunas, array $citas): array
    {
        $alertas = [];

        foreach ($vacunas as $v) {
            if (in_array($v['estado'], ['vencido', 'proximo'], true)) {
                $alertas[] = [
                    'tipo' => 'vacuna',
                    'nivel' => $v['estado'] === 'vencido' ? 'danger' : 'warning',
                    'mensaje' => $v['vacuna'].': '.$v['estado_label'].($v['proxima'] ? " ({$v['proxima']})" : ''),
                ];
            }
        }

        foreach ($citas as $c) {
            $alertas[] = [
                'tipo' => 'cita',
                'nivel' => 'info',
                'mensaje' => 'Cita programada: '.($c['servicio'] ?? $c['motivo'] ?? 'Consulta').' — '.$c['fecha'],
            ];
        }

        return $alertas;
    }

    private function estadoFecha($fecha): string
    {
        if (! $fecha) {
            return 'sin_programar';
        }

        $fecha = $fecha instanceof Carbon ? $fecha : Carbon::parse($fecha);
        $hoy = now()->startOfDay();

        if ($fecha->lt($hoy)) {
            return 'vencido';
        }

        if ($fecha->lte($hoy->copy()->addDays(15))) {
            return 'proximo';
        }

        return 'al_dia';
    }

    private function etiquetaEstado(string $estado): string
    {
        return match ($estado) {
            'vencido' => 'Vencido',
            'proximo' => 'Próximo a vencer',
            'al_dia' => 'Al día',
            'sin_programar' => 'Sin próxima fecha',
            default => 'Pendiente',
        };
    }
}
