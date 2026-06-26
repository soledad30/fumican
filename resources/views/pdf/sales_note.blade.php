<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota de Venta #{{ $salesNote->id }}</title>
    <style>
        @page {
            margin: 30mm 15mm 20mm;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
        }

        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 20px 0 10px;
            background: #fff;
            border-bottom: 1px solid #ddd;
            z-index: 1000;
        }

        .logo {
            float: left;
            width: 120px;
        }

        .company-info {
            float: right;
            text-align: right;
            font-size: 10px;
            line-height: 1.2;
        }

        .title {
            clear: both;
            text-align: center;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .title h1 {
            font-size: 24px;
            margin: 0;
            color: #2c3e50;
        }

        .title small {
            display: block;
            font-size: 12px;
            color: #777;
            margin-top: 4px;
        }

        main {
            margin-top: 160px;
            margin-bottom: 50px;
        }

        .general-info {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: collapse;
        }

        .general-info td {
            padding: 4px 8px;
            vertical-align: top;
        }

        .general-info .label {
            font-weight: bold;
            width: 120px;
        }

        table.details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            page-break-inside: auto;
        }

        table.details th,
        table.details td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        table.details th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        table.details tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .total {
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
        }

        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            padding-top: 5px;
            background: #fff;
        }

        .pagenum:before {
            content: counter(page);
        }
    </style>
</head>

<body>
    <header>
        <div class="logo">
            <img src="{{ public_path('images/logo.png') }}" alt="Logo" style="max-width: 100%;">
        </div>
        <div class="company-info">
            Mi Empresa S.A.<br>
            Av. Central 123, Ciudad<br>
            Tel: (4XX) 123-4567<br>
            correo@empresa.com
        </div>
        <div class="title">
            <h1>Nota de Venta</h1>
            <small>#{{ $salesNote->id }} &mdash;
                {{ \App\Support\FormatoPdf::fecha($salesNote->fecha_venta ?? $salesNote->sale_date) }}
            </small>
        </div>
    </header>
    <main>
        <table class="general-info">
            <tr>
                <td class="label">Almacén:</td>
                <td>{{ $salesNote->almacen?->nombre ?? $salesNote->warehouse?->name ?? 'N/A' }}</td>
                <td class="label">Usuario:</td>
                <td>{{ \App\Support\FormatoPdf::nombreCompleto($salesNote->usuario ?? $salesNote->user) }}</td>
            </tr>
            <tr>
                <td class="label">Cliente:</td>
                <td>{{ \App\Support\FormatoPdf::nombreCompleto($salesNote->cliente ?? $salesNote->customer) }}</td>
                <td class="label">Generado:</td>
                <td>{{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</td>
            </tr>
        </table>

        <h3>Detalle de Venta</h3>
        <table class="details">
            <thead>
                <tr>
                    <th style="width:50%;">Medicamento</th>
                    <th style="width:15%; text-align:right;">Cant.</th>
                    <th style="width:15%; text-align:right;">Precio (Bs)</th>
                    <th style="width:20%; text-align:right;">Subtotal (Bs)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($salesNoteDetails as $d)
                    <tr>
                        <td>{{ $d->medicament?->nombre ?? $d->medicament?->name ?? 'N/A' }}</td>
                        <td style="text-align:right;">{{ $d->quantity }}</td>
                        <td style="text-align:right;">{{ number_format($d->sale_price, 2) }}</td>
                        <td style="text-align:right;">{{ number_format($d->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="total">
            Total: Bs {{ number_format($salesNote->total_amount, 2) }}
        </div>
    </main>
    <footer>
        <div>Nota de Venta generada automáticamente &bull; Página <span class="pagenum"></span></div>
    </footer>
</body>

</html>
