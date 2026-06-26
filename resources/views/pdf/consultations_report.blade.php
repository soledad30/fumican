<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Consultas Médicas</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; }
        .header p { margin: 0; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f2f2f2; }
        .filters { margin-bottom: 15px; background-color:#f8f9fa; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Consultas Médicas</h1>
        <p>Generado el: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    @if(array_filter($filters))
    <div class="filters">
        <strong>Filtros Aplicados:</strong>
        @if(!empty($filters['search_term'])) <span>Término: <em>{{ $filters['search_term'] }}</em>;</span> @endif
        @if(!empty($filters['date_from'])) <span>Desde: <em>{{ \Carbon\Carbon::parse($filters['date_from'])->format('d/m/Y') }}</em>;</span> @endif
        @if(!empty($filters['date_to'])) <span>Hasta: <em>{{ \Carbon\Carbon::parse($filters['date_to'])->format('d/m/Y') }}</em>;</span> @endif
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Mascota</th>
                <th>Propietario</th>
                <th>Motivo de Consulta</th>
                <th>Veterinario/a</th>
            </tr>
        </thead>
        <tbody>
            @forelse($consultations as $consultation)
                <tr>
                    <td>{{ $consultation->id }}</td>
                    <td>{{ \App\Support\FormatoPdf::fecha($consultation->fecha ?? $consultation->creado_en) }}</td>
                    <td>{{ $consultation->mascota?->nombre ?? $consultation->mascota?->name ?? $consultation->pet?->name ?? $consultation->pet_name ?? 'N/A' }}</td>
                    <td>{{ \App\Support\FormatoPdf::nombreCompleto($consultation->mascota?->propietario ?? $consultation->mascota?->owner ?? null) }}</td>
                    <td>{{ $consultation->motivo ?? $consultation->reason ?? 'N/A' }}</td>
                    <td>{{ \App\Support\FormatoPdf::nombreCompleto($consultation->veterinario ?? $consultation->user ?? null) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">No se encontraron consultas con los filtros aplicados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
