<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Historial Clínico de {{ $mascota->nombre ?? $mascota->name }}</title>
    <style>
        body { font-family: Helvetica, sans-serif; font-size: 11px; color: #333; }
        @page { margin: 40px 50px; }
        .header { border-bottom: 1px solid #ddd; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { font-size: 22px; margin: 0; color: #007bff; }
        .header h2 { font-size: 16px; margin: 0; }
        .pet-info { background: #f8f9fa; padding: 12px; border-radius: 5px; margin-bottom: 18px; }
        .pet-info table { width: 100%; }
        .pet-info td { padding: 3px; }
        .resumen { margin-bottom: 18px; }
        .resumen span { display: inline-block; margin-right: 16px; }
        .section { margin-bottom: 18px; page-break-inside: avoid; }
        .section h3 { font-size: 13px; color: #007bff; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .evento { border: 1px solid #eee; border-radius: 4px; margin-bottom: 10px; padding: 10px; }
        .evento-header { font-weight: bold; margin-bottom: 6px; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; background: #e9ecef; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.data th, table.data td { border: 1px solid #ddd; padding: 5px; text-align: left; }
        table.data th { background: #f1f3f5; }
        .footer { position: fixed; bottom: -20px; left: 0; right: 0; text-align: center; font-size: 9px; color: #777; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Clínica Veterinaria "Fumican"</h1>
        <h2>Historial clínico unificado</h2>
        <p>Generado el: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="pet-info">
        <table>
            <tr>
                <td><strong>Paciente:</strong> {{ $mascota->nombre ?? $mascota->name }}</td>
                <td><strong>Especie:</strong> {{ $mascota->raza?->especie?->nombre ?? $mascota->breed?->specie?->name ?? 'N/A' }}</td>
                <td><strong>Raza:</strong> {{ $mascota->raza?->nombre ?? $mascota->breed?->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Sexo:</strong> {{ $mascota->gender }}</td>
                <td><strong>Nacimiento:</strong>
                    {{ $mascota->birth_date ? \Carbon\Carbon::parse($mascota->birth_date)->format('d/m/Y') : 'N/A' }}
                </td>
                <td><strong>Color:</strong> {{ $mascota->color ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td colspan="3">
                    <strong>Propietario:</strong>
                    {{ \App\Support\FormatoPdf::nombreCompleto($mascota->propietario ?? $mascota->owner) }}
                    (CI: {{ $mascota->propietario?->ci ?? $mascota->owner?->ci ?? 'N/A' }})
                    | <strong>Tel:</strong> {{ $mascota->propietario?->telefono ?? $mascota->owner?->phone_number ?? 'N/A' }}
                </td>
            </tr>
        </table>
    </div>

    <div class="resumen">
        <span><strong>Consultas:</strong> {{ $resumen['total_consultas'] }}</span>
        <span><strong>Completadas:</strong> {{ $resumen['consultas_completadas'] }}</span>
        <span><strong>Reservadas:</strong> {{ $resumen['consultas_reservadas'] }}</span>
        <span><strong>Vacunas:</strong> {{ $resumen['total_vacunas'] }}</span>
        <span><strong>Pagos:</strong> {{ $resumen['total_pagos'] }}</span>
    </div>

    <div class="section">
        <h3>Línea de tiempo clínica</h3>
        @forelse($eventos as $evento)
            <div class="evento">
                <div class="evento-header">
                    {{ $evento['titulo'] }}
                    @if(!empty($evento['fecha']))
                        — {{ \Carbon\Carbon::parse($evento['fecha'])->format('d/m/Y H:i') }}
                    @endif
                    @if(!empty($evento['estado_label']))
                        <span class="badge">{{ $evento['estado_label'] }}</span>
                    @endif
                </div>

                @if($evento['tipo'] === 'consulta')
                    <p><strong>Motivo:</strong> {{ $evento['motivo'] ?? 'N/A' }}</p>
                    <p><strong>Diagnóstico:</strong> {{ $evento['diagnostico'] ?? 'N/A' }}</p>
                    <p><strong>Veterinario:</strong> {{ $evento['veterinario'] ?? 'N/A' }}</p>
                    @if(!empty($evento['costo']))
                        <p><strong>Costo:</strong> Bs. {{ number_format($evento['costo'], 2) }}</p>
                    @endif
                    @if(!empty($evento['tratamientos']) && count($evento['tratamientos']))
                        <p><strong>Tratamientos:</strong></p>
                        <ul>
                            @foreach($evento['tratamientos'] as $t)
                                <li>{{ $t['producto'] ?? 'Producto' }} (x{{ $t['cantidad'] ?? 1 }}) — {{ $t['instrucciones'] ?? '' }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if(!empty($evento['pagos']) && count($evento['pagos']))
                        <p><strong>Pagos asociados:</strong>
                            @foreach($evento['pagos'] as $p)
                                Bs. {{ number_format($p['monto'], 2) }} ({{ $p['tipo_pago'] }}/{{ $p['metodo_pago'] }})@if(!$loop->last), @endif
                            @endforeach
                        </p>
                    @endif
                @elseif($evento['tipo'] === 'vacuna')
                    <p><strong>Próxima dosis:</strong> {{ $evento['proxima'] ?? 'N/A' }}</p>
                    <p><strong>Aplicado por:</strong> {{ $evento['veterinario'] ?? 'N/A' }}</p>
                    @if(!empty($evento['notas']))<p>{{ $evento['notas'] }}</p>@endif
                @elseif($evento['tipo'] === 'tratamiento')
                    <p>Cantidad: {{ $evento['cantidad'] ?? 1 }} — {{ $evento['instrucciones'] ?? '' }}</p>
                    @if(!empty($evento['notas']))<p>{{ $evento['notas'] }}</p>@endif
                @elseif($evento['tipo'] === 'pago')
                    <p><strong>Monto:</strong> Bs. {{ number_format($evento['monto'], 2) }}</p>
                    <p>{{ ucfirst($evento['tipo_pago'] ?? '') }} — {{ $evento['metodo_pago'] ?? '' }}</p>
                @endif
            </div>
        @empty
            <p style="text-align:center;">Sin eventos clínicos registrados.</p>
        @endforelse
    </div>

    <div class="footer">
        <p>Fumican Vet — Historial clínico</p>
    </div>
</body>
</html>
