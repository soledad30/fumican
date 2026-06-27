<?php

return [
    /*
    | Porcentaje mínimo de anticipo al reservar sin cuenta (página pública).
    */
    'porcentaje_anticipo' => (int) env('RESERVA_PORCENTAJE_ANTICIPO', 20),

    'minutos_gracia_no_asistio' => (int) env('RESERVA_MINUTOS_GRACIA', 20),

    /*
    | Hora de cierre de la clínica. Tras esta hora del día de la cita, si no hubo
    | check-in, la reserva pasa a «no asistió» y se pierde el anticipo.
    */
    'hora_cierre_clinica' => env('RESERVA_HORA_CIERRE', '19:00'),

    'horarios' => ['09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00', '17:00'],
];
