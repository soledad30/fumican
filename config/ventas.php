<?php

return [
    /** IVA por defecto (Bolivia: 13 %) */
    'iva_porcentaje' => (float) env('VENTAS_IVA', 0.13),

    /** Margen de utilidad sobre precio de compra (30 %) */
    'margen_default' => (float) env('VENTAS_MARGEN', 0.30),
];
