<?php

namespace App\Listeners;

use App\Services\Auditoria\BitacoraService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;

class RegistrarEventosAutenticacion
{
    public function __construct(protected BitacoraService $bitacora) {}

    public function handleLogin(Login $event): void
    {
        $email = $event->user->email ?? 'desconocido';
        $this->bitacora->loginExitoso($event->user->id, $email);
    }

    public function handleFailed(Failed $event): void
    {
        $email = $event->credentials['email'] ?? 'desconocido';
        $this->bitacora->loginFallido($email);
    }
}
