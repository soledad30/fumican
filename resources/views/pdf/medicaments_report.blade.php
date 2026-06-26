<!doctype html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        /** ajustes mínimos para impresión A4 horizontal **/
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1em;
        }

        th,
        td {
            border: 1px solid #444;
            padding: 4px;
            text-align: left;
        }

        th {
            background: #eee;
        }

        .header {
            text-align: center;
        }

        .filters {
            margin-top: .5em;
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php use Carbon\Carbon; @endphp

    <div class="header">
        <h1>Reporte de Medicamentos</h1>
        <p>Fecha: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    {{-- Mostrar filtros aplicados --}}
    <div class="filters">
        <strong>Filtros aplicados:</strong>
        <ul>
            @foreach ($filters as $key => $value)
                @if ($value !== '' && $value !== null)
                    <li>
                        @switch($key)
                            @case('name')
                                Nombre:
                            @break

                            @case('dosage')
                                Dosificación:
                            @break

                            @case('manufacturer')
                                Fabricante:
                            @break

                            @case('expiration_from')
                                Expiración desde:
                            @break

                            @case('expiration_to')
                                Expiración hasta:
                            @break

                            @case('controlled_substance')
                                Controlada:
                            @break

                            @case('category_id')
                                Categoría ID:
                            @break
                        @endswitch
                        {{ $value }}
                    </li>
                @endif
            @endforeach
            @if (collect($filters)->filter()->isEmpty())
                <li>— Sin filtros (todos los registros) —</li>
            @endif
        </ul>
    </div>

    {{-- Tabla de resultados --}}
    <table>
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Dosificación</th>
                <th>Fabricante</th>
                <th>Expiración</th>
                <th>Controlada</th>
                <th>Categoría</th>
                <th>Modificado</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($medicaments as $m)
                <tr>
                    <td>{{ $m->nombre ?? $m->name ?? 'N/A' }}</td>
                    <td>{{ $m->dosificacion ?? $m->dosage ?? 'N/A' }}</td>
                    <td>{{ $m->fabricante ?? $m->manufacturer ?? 'N/A' }}</td>
                    <td>{{ \App\Support\FormatoPdf::fecha($m->fecha_vencimiento ?? $m->expiration_date) }}</td>
                    <td>{{ (! empty($m->sustancia_controlada) || ($m->controlled_substance ?? '') === 'yes') ? 'Sí' : 'No' }}</td>
                    <td>{{ $m->categoria?->nombre ?? $m->category?->name ?? 'N/A' }}</td>
                    <td>{{ \App\Support\FormatoPdf::fecha($m->actualizado_en ?? $m->updated_at, 'd/m/Y H:i') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
