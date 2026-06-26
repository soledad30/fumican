<?php

namespace App\Traits;

use App\Services\Auditoria\BitacoraService;

trait RegistraBitacora
{
    protected function registrarBitacora(
        string $accion,
        string $modulo,
        ?string $descripcion = null,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null
    ): void {
        try {
            app(BitacoraService::class)->registrar(
                $accion,
                $modulo,
                $descripcion,
                $datosAnteriores,
                $datosNuevos
            );
        } catch (\Throwable) {
            // Auditoría local opcional
        }
    }
}
