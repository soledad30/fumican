<?php

namespace App\Services\Reservations;

use App\Models\Ventas\Inventario;
use App\Repositories\Reservations\ReserveRepository;

class ReserveService
{
    public function __construct(protected ReserveRepository $reserveRepository) {}

   
}
