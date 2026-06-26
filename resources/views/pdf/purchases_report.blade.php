<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 20mm 15mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        h2 {
            text-align: center;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 6px;
            text-align: left;
        }

        th {
            background: #f5f5f5;
        }

        footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
        }

        .pagenum:before {
            content: counter(page);
        }
    </style>
</head>

<body>

    <h2>Reporte de Notas de Compra</h2>

    @if (!empty($filters['supplier_id']))
        <p><strong>Proveedor:</strong> {{ \App\Models\Ventas\Proveedor::find($filters['supplier_id'])->name }}</p>
    @endif
    @if (!empty($filters['warehouse_id']))
        <p><strong>Almacén:</strong> {{ \App\Models\Ventas\Almacen::find($filters['warehouse_id'])->name }}</p>
    @endif
    @if (!empty($filters['date_from']) || !empty($filters['date_to']))
        <p>
            <strong>Período:</strong>
            {{ $filters['date_from'] ?? '—' }} hasta {{ $filters['date_to'] ?? '—' }}
        </p>
    @endif

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Almacén</th>
                <th>Total (Bs)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchases as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td>{{ \App\Support\FormatoPdf::fecha($p->fecha_compra ?? $p->purchase_date) }}</td>
                    <td>{{ $p->proveedor?->nombre ?? $p->supplier?->name ?? 'N/A' }}</td>
                    <td>{{ $p->almacen?->nombre ?? $p->warehouse?->name ?? 'N/A' }}</td>
                    <td style="text-align:right;">{{ number_format($p->monto_total ?? $p->total_amount ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <footer>
        Reporte generado: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }} &mdash; Página <span class="pagenum"></span>
    </footer>
</body>

</html>
