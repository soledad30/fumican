<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reserva - Veterinaria Fumican</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #333;
            font-size: 14px;
            line-height: 1.6;
        }
        .container {
            width: 90%;
            margin: auto;
            padding: 20px;
        }
        h1 {
            text-align: center;
            color: #1d4ed8;
        }
        .info {
            margin: 20px 0;
            padding: 10px;
            border: 1px solid #d1d5db;
            background-color: #f9fafb;
            border-radius: 6px;
        }
        .info p {
            margin: 6px 0;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Confirmación de Reserva</h1>
        <div class="info">
            <p><strong>Nombre:</strong> {{ $data['name'] }}</p>
            <p><strong>Teléfono:</strong> {{ $data['phone'] }}</p>
            <p><strong>Correo:</strong> {{ $data['email'] }}</p>
            <p><strong>Mascota:</strong> {{ $data['petName'] }}</p>
            <p><strong>Servicio:</strong> {{ $data['service'] }}</p>
            <p><strong>Fecha:</strong> {{ $data['date'] }}</p>
            <p><strong>Horario:</strong> {{ $data['timeSlot'] }}</p>
            @if (!empty($data['comment']))
                <p><strong>Comentario:</strong> {{ $data['comment'] }}</p>
            @endif
        </div>
        <div class="footer">
            Gracias por confiar en Veterinaria Fumican<br>
            Santa Cruz de la Sierra | Cel: 700-00000
        </div>
    </div>
</body>
</html>
