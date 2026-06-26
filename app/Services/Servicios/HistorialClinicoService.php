<?php

namespace App\Services\Servicios;

use App\Enums\EstadoConsultaEnum;
use App\Models\Servicios\Mascota;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class HistorialClinicoService
{
    public function obtenerHistorial(Mascota $mascota): array
    {
        $mascota->load([
            'propietario',
            'raza.especie',
            'consultas' => fn ($q) => $q->with([
                'servicio',
                'veterinario',
                'tratamientos.producto',
                'pagos',
            ])->orderByDesc('fecha')->orderByDesc('creado_en'),
            'historialVacunacion' => fn ($q) => $q->with(['vacuna', 'veterinario'])
                ->orderByDesc('fecha_aplicacion'),
            'pagos' => fn ($q) => $q->with(['servicio', 'notaVenta'])
                ->whereNull('consulta_id')
                ->orderByDesc('fecha_pago'),
        ]);

        $eventos = collect();

        foreach ($mascota->consultas as $consulta) {
            $fecha = $consulta->fecha
                ? Carbon::parse($consulta->fecha.($consulta->hora ? ' '.$consulta->hora : ''))
                : $consulta->creado_en;

            $eventos->push([
                'tipo' => 'consulta',
                'fecha' => $fecha?->toIso8601String(),
                'titulo' => 'Consulta — '.($consulta->servicio?->nombre ?? 'General'),
                'estado' => $consulta->estado,
                'estado_label' => EstadoConsultaEnum::labels()[$consulta->estado] ?? $consulta->estado,
                'motivo' => $consulta->motivo,
                'diagnostico' => $consulta->diagnostico,
                'costo' => $consulta->costo_consulta,
                'veterinario' => $consulta->veterinario?->full_name,
                'pagos' => $consulta->pagos->map(fn ($p) => [
                    'monto' => $p->monto,
                    'tipo_pago' => $p->tipo_pago,
                    'metodo_pago' => $p->metodo_pago,
                    'fecha_pago' => $p->fecha_pago,
                ]),
                'tratamientos' => $consulta->tratamientos->map(fn ($t) => [
                    'producto' => $t->producto?->nombre,
                    'cantidad' => $t->cantidad,
                    'instrucciones' => $t->instrucciones_dosis,
                ]),
            ]);

            foreach ($consulta->tratamientos as $tratamiento) {
                $eventos->push([
                    'tipo' => 'tratamiento',
                    'fecha' => $fecha?->toIso8601String(),
                    'titulo' => 'Tratamiento — '.($tratamiento->producto?->nombre ?? 'Producto'),
                    'cantidad' => $tratamiento->cantidad,
                    'instrucciones' => $tratamiento->instrucciones_dosis,
                    'notas' => $tratamiento->notas,
                    'consulta_id' => $consulta->id,
                ]);
            }
        }

        foreach ($mascota->historialVacunacion as $registro) {
            $eventos->push([
                'tipo' => 'vacuna',
                'fecha' => $registro->fecha_aplicacion?->toIso8601String(),
                'titulo' => 'Vacuna — '.($registro->vacuna?->nombre ?? 'N/A'),
                'proxima' => $registro->fecha_proxima?->format('d/m/Y'),
                'notas' => $registro->notas,
                'veterinario' => $registro->veterinario?->full_name,
            ]);
        }

        foreach ($mascota->pagos as $pago) {
            $eventos->push([
                'tipo' => 'pago',
                'fecha' => $pago->fecha_pago?->toIso8601String() ?? $pago->creado_en?->toIso8601String(),
                'titulo' => $pago->nota_venta_id
                    ? 'Pago nota de venta #'.$pago->nota_venta_id
                    : 'Pago — '.($pago->servicio?->nombre ?? 'Servicio'),
                'monto' => $pago->monto,
                'tipo_pago' => $pago->tipo_pago,
                'metodo_pago' => $pago->metodo_pago,
            ]);
        }

        return [
            'mascota' => $mascota,
            'eventos' => $this->ordenarEventos($eventos),
            'resumen' => [
                'total_consultas' => $mascota->consultas->count(),
                'consultas_completadas' => $mascota->consultas->where('estado', EstadoConsultaEnum::COMPLETADA->value)->count(),
                'consultas_reservadas' => $mascota->consultas->where('estado', EstadoConsultaEnum::RESERVADA->value)->count(),
                'total_vacunas' => $mascota->historialVacunacion->count(),
                'total_pagos' => $mascota->pagos->count() + $mascota->consultas->sum(fn ($c) => $c->pagos->count()),
            ],
        ];
    }

    private function ordenarEventos(Collection $eventos): Collection
    {
        return $eventos
            ->sortByDesc(fn ($e) => $e['fecha'] ?? '')
            ->values();
    }
}
